<?php
session_start();
require 'config.php';  // Include database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Hash the password
    
    // Insert the user into the database
    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $password]);

    // Redirect to login page after registration
    header('Location: index.php');  // Redirect to login page
    exit;
}
?>

<!-- Registration Form -->
<form method="POST">
    <input type="email" name="email" required placeholder="Enter email">
    <input type="password" name="password" required placeholder="Enter password">
    <button type="submit">Register</button>
</form>