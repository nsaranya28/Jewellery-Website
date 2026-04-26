<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Self-Contained SMTP Test</h1>";

$host = 'smtp.gmail.com';
$port = 465;
$user = 'saranya02022005@gmail.com';
$pass = 'ecdlwebegacdoyti'; // Cleaned up
$from = 'saranya02022005@gmail.com';

echo "Testing connection to $host:$port...<br>";

$prefix = 'ssl://';
$socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);

if (!$socket) {
    echo "<b style='color:red;'>Connection FAILED: $errstr ($errno)</b>";
} else {
    echo "<b style='color:green;'>Connection SUCCESS!</b><br>";
    echo "S: " . fgets($socket, 515) . "<br>";
    fwrite($socket, "EHLO localhost\r\n");
    echo "C: EHLO localhost<br>";
    echo "S: " . fgets($socket, 515) . "<br>";
    fclose($socket);
}
?>
