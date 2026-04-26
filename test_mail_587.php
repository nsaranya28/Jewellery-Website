<?php
$host = "smtp.gmail.com";
$port = 587;
$user = "nsaranya282@gmail.com";
$pass = "ecdlwebegacdoyti"; // Hardcoded for test

echo "Testing Port 587 (TLS)...\n";
$socket = fsockopen($host, $port, $errno, $errstr, 10);
if (!$socket) {
    die("Connection failed: $errstr\n");
}
echo fgets($socket, 512);
fwrite($socket, "EHLO localhost\r\n");
echo fgets($socket, 512); echo fgets($socket, 512); echo fgets($socket, 512);
fwrite($socket, "STARTTLS\r\n");
echo fgets($socket, 512);
stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
fwrite($socket, "EHLO localhost\r\n");
echo fgets($socket, 512);
fwrite($socket, "AUTH LOGIN\r\n");
echo fgets($socket, 512);
fwrite($socket, base64_encode($user) . "\r\n");
echo fgets($socket, 512);
fwrite($socket, base64_encode($pass) . "\r\n");
echo fgets($socket, 512);
fwrite($socket, "QUIT\r\n");
fclose($socket);
