<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "PHP is working<br>";

$file = __DIR__ . "/config/database.php";

echo "Looking for: " . $file . "<br>";
echo file_exists($file) ? "config/database.php FOUND<br>" : "config/database.php MISSING<br>";

if (file_exists($file)) {
    require_once $file;
    echo "Database connected successfully!";
}