<?php
//For this file's php, the entire php statements until before the html was created by Haaziq and edited by Si Thu during UI implementation.
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
        echo"<script>alert('Invalid email or password. Reloading Webpage...')</script>";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST'&& isset($_POST['signup_email'], $_POST['signup_password'])) {
    $email = $_POST['signup_email'];
    $password = password_hash($_POST['signup_password'], PASSWORD_DEFAULT);  // Hash the password
    
    // check if user registered 
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
   
    if ($user) {
        echo"<script>alert('Existing account detected. Reloading Webpage...')</script>";
    } else {
        // Insert the new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $password]);

    // Redirect to login page after registration
    header('Location: login.php');  // Redirect to login page
    exit;}
}
?>
<!--The entire html and inline php statement has been created by ChunZen before and edited by Si Thu during UI implementation-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
    <script src="javascript/form_popout.js"></script>
</head>
<body>
    <marquee behavior="scroll" direction="left">Welcome to our Humble Abode | Please refer to tables folder should you require SQL code |
         I hope you find this UI satisfactory! | Mr Shaw Ping Lee is the best lecturer of all time!
    </marquee>
    <div id="title"><h1>Streamline</h1></div>
        <div><h3 id = "subtitle">A web-based project to help you manage your stressful task with teammates</h3></span></div>
    <div class="card-container" id ="card-container">
        <div class="card">
            <img src="css/drawables/login.png" alt="login" title="Login" onclick="openFormL()">
        </div>
        <div class="card">
            <img src="css/drawables/singup.png" alt="register" title="Sign Up" onclick="openFormR()">
        </div>
        <div class="card">
            <img src="css/drawables/about.png" alt="forgot password" title="About" onclick="openAbout()">
        </div>
    </div>
    <div class="form-popup" id="login">
        
        <form class="form-container" form method="POST" target="_self">
          <h4>Login</h4>
          <br>
          <label for="email" class="email-label"><b>Email</b></label>
          <input type="text" placeholder="Enter Email" name="login_email" required>
          <br>
          <label for="psw"><b>Password</b></label>
          <input type="password" placeholder="Enter Password" name="login_password" required>
          <br>
          <div class="button-container">
            <button type="submit" class="btn">Login</button>
            <button type="button" class="btn cancel" onclick="closeForm()">Close</button>
          </div>
        </form>
    </div>
    <div class="form-popup" id="signup">
        <form class="form-container" form method="POST" target="_self">
          <h4>Sign Up</h4>
          <br>
          <label for="email" class ="email-label"><b>Email</b></label>
          <input type="text" placeholder="Enter Email" name="signup_email" required>
          <br>
          <label for="psw"><b>Password</b></label>
          <input type="password" placeholder="Enter Password" name="signup_password" required>
          <br>
          <div class ="button-container">
            <button type="submit" class="button">Sign Up</button>
            <button type="button" class="btn cancel" onclick="closeForm()">Close</button>
          </div>
        </form>
    </div>
    <div class="form-popup" id="about">
        <h5>Made by students of: &nbsp; </h5>
        <p><b style="color: #b60000;">S</b>ingapore &nbsp; <b style="color: #b60000;">P</b>olytechnic</p>
        <form class="form-container">
            <button type="button" class="btn cancel" onclick="closeForm()">Close</button>
        </form>
    </div>
</body>
<footer>
    <p>Designed by Hazziq, Lewis, Chun Zen and Si Thu.</p>
</footer>
</html>
