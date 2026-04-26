<?php
/**
 * PHPMailerLite - A simplified SMTP client for Gmail
 * Designed for environments where full PHPMailer is not installed.
 */
class PHPMailerLite {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $fromName;

    private $log = [];

    public function __construct($host, $port, $user, $pass, $from, $fromName) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->from = $from;
        $this->fromName = $fromName;
    }

    public function getLastError() {
        return $this->lastError . "\n\nSMTP Log:\n" . implode("", $this->log);
    }

    public function send($to, $subject, $message) {
        $this->log = [];
        try {
            if (!extension_loaded('openssl')) {
                $this->lastError = "PHP OpenSSL extension is not enabled.";
                return false;
            }

            $prefix = ($this->port == 465) ? 'ssl://' : '';
            $socket = @fsockopen($prefix . $this->host, $this->port, $errno, $errstr, 15);
            if (!$socket) {
                $this->lastError = "Connection failed: $errstr ($errno)";
                return false;
            }

            $this->log[] = "S: " . $this->getResponse($socket);
            $helloHost = $_SERVER['HTTP_HOST'] ?? gethostname() ?? 'localhost';
            $this->log[] = "C: EHLO $helloHost\n";
            fwrite($socket, "EHLO " . $helloHost . "\r\n");
            $this->log[] = "S: " . $this->getResponse($socket);

            $this->log[] = "C: AUTH LOGIN\n";
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->log[] = "S: " . $this->getResponse($socket);
            
            $this->log[] = "C: [USER]\n";
            fwrite($socket, base64_encode(trim($this->user)) . "\r\n");
            $this->log[] = "S: " . $this->getResponse($socket);
            
            $this->log[] = "C: [PASS]\n";
            $cleanPass = str_replace(' ', '', trim($this->pass));
            fwrite($socket, base64_encode($cleanPass) . "\r\n");
            $res = $this->getResponse($socket);
            $this->log[] = "S: " . $res;
            
            if (strpos($res, '235') === false) {
                $this->lastError = "Authentication failed.";
                return false;
            }

            fwrite($socket, "MAIL FROM: <{$this->from}>\r\n");
            $this->getResponse($socket);
            fwrite($socket, "RCPT TO: <{$to}>\r\n");
            $this->getResponse($socket);
            fwrite($socket, "DATA\r\n");
            $this->getResponse($socket);

            $headers = "MIME-Version: 1.0\r\nContent-type: text/plain; charset=utf-8\r\n";
            $headers .= "To: <{$to}>\r\nFrom: {$this->fromName} <{$this->from}>\r\n";
            $headers .= "Subject: {$subject}\r\nDate: " . date('r') . "\r\n";

            fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
            $this->getResponse($socket);
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function getResponse($socket) {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }
}
