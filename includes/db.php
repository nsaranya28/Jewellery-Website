<?php
// Database connection
define('DB_HOST', 'localhost');
define('DB_NAME', 'jewellery_db');
define('DB_USER', 'root');
define('DB_PASS', 'pass');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:30px;background:#fff3cd;border:1px solid #c9a227;border-radius:10px;margin:20px;">
        <h3 style="color:#856404;">⚠️ Database Connection Failed</h3>
        <p>Please make sure:</p>
        <ol>
          <li>XAMPP MySQL is running</li>
          <li>You imported <code>database/jewellery.sql</code> via <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a></li>
        </ol>
        <p><strong>Error:</strong> '.$e->getMessage().'</p>
    </div>');
}
