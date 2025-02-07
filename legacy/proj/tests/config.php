<?php
$host = "localhost";
$dbname = "management";  // Replace with your database name
$username = "root";        // Default XAMPP MySQL username
$password = "";            // Default XAMPP MySQL password (empty by default)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
