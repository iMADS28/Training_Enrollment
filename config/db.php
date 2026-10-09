<?php
// config/db.php
// Creates the single PDO connection via Database Singleton

require_once __DIR__ . '/../classes/Database.php';

$dsn = "mysql:host=localhost;dbname=training_db;charset=utf8mb4";
$user = "root";
$pass = "";

$db = Database::getInstance($dsn, $user, $pass);
