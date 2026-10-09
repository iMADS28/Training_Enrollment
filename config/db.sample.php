<?php
// config/db.sample.php
// Sample database configuration template for GitHub submission

require_once __DIR__ . '/../classes/Database.php';

$host = "localhost";
$dbname = "training_db";
$charset = "utf8mb4";
$user = "your_db_username";
$pass = "your_db_password";

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$db = Database::getInstance($dsn, $user, $pass);
