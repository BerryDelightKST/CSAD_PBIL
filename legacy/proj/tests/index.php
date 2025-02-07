    <?php
session_start();
require 'config.php';  // Include database connection

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');  // Redirect to profile page
    exit;
}
?>

<?php
// Handle the login logic if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_email'], $_POST['login_password'])) {
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];

    // Fetch the stored hash for the given email
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user exists and the password is correct
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables and redirect to profile page
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $email;
        header('Location: profile.php');  // Redirect to profile page
        exit;
    } else {
        $login_error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Project Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f9;
        }
        .container {
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 2em;
            color: #333;
        }
        .buttons a, .form button {
            padding: 10px 20px;
            font-size: 1.2em;
            color: white;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            display: inline-block;
            margin-top: 10px;
        }
        .buttons a:hover, .form button:hover {
            background-color: #0056b3;
        }
        .form input {
            padding: 10px;
            font-size: 1em;
            margin: 5px 0;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form {
            margin-top: 20px;
        }
        .error {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Welcome to Project Management System</h1>

    <!-- Display login form if not logged in -->
    <div class="form">
        <h2>Login</h2>
        <?php if (isset($login_error)) { echo "<p class='error'>$login_error</p>"; } ?>

        <form method="POST">
            <input type="email" name="login_email" required placeholder="Enter your email">
            <input type="password" name="login_password" required placeholder="Enter your password">
            <button type="submit">Login</button>
        </form>
    </div>

    <p>Or</p>

    <div class="buttons">
        <a href="register.php">Register</a> <!-- Link to the register page -->
    </div>
</div>

</body>
</html>
