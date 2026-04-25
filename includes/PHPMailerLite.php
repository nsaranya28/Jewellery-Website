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

    private $lastError;

    public function __construct($host, $port, $user, $pass, $from, $fromName) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->from = $from;
        $this->fromName = $fromName;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function send($to, $subject, $message) {
        try {
            if (!extension_loaded('openssl')) {
                $this->lastError = "PHP OpenSSL extension is not enabled. Please enable it in php.ini.";
                return false;
            }

            $socket = @fsockopen('ssl://' . $this->host, 465, $errno, $errstr, 15);
            if (!$socket) {
                $this->lastError = "Connection failed: $errstr ($errno)";
                return false;
            }

            $this->getResponse($socket);
            fwrite($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $this->getResponse($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            $this->getResponse($socket);
            fwrite($socket, base64_encode($this->user) . "\r\n");
            $this->getResponse($socket);
            fwrite($socket, base64_encode($this->pass) . "\r\n");
            $res = $this->getResponse($socket);
            if (strpos($res, '235') === false) {
                $this->lastError = "Authentication failed: " . $res;
                return false;
            }

            fwrite($socket, "MAIL FROM: <{$this->from}>\r\n");
            $this->getResponse($socket);
            fwrite($socket, "RCPT TO: <{$to}>\r\n");
            $this->getResponse($socket);

            fwrite($socket, "DATA\r\n");
            $this->getResponse($socket);

            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/plain; charset=utf-8\r\n";
            $headers .= "To: <{$to}>\r\n";
            $headers .= "From: {$this->fromName} <{$this->from}>\r\n";
            $headers .= "Subject: {$subject}\r\n";
            $headers .= "Date: " . date('r') . "\r\n";

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
