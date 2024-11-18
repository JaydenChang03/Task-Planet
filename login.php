<?php
session_start();
require('db_connect.php');

$usernameInvalid = "";
$passwordInvalid = "";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $query = "SELECT * FROM users WHERE user_name = '$username' AND password = '$password'";
    $result = mysqli_query($dbc, $query);
    $row = mysqli_fetch_array($result);
    if (mysqli_num_rows($result) == 1) {
        $_SESSION['username'] = $username;
        $_SESSION['userid'] = $row['user_id'];
        header("Location: home.php");
        exit();
    } else {
        $usernameInvalid = "Username is invalid";
        $passwordInvalid = "Password is invalid";
        session_unset();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Planet - Log In</title>
    <link rel="stylesheet" href="css/loginstyle.css">
</head>
<body>
<div class="banner">
    <div class="navbar">
        <a href="home.php">
            <img src="image/logo.png" class="logo" alt="Logo">
        </a>
        <ul>
            <?php
            if (isset($_SESSION['userid'])) {
                echo '<li><a href="userprofile.php">Profile</a></li>';
                echo '<li><a href="logout.php">Logout</a></li>'; // Logout link
            } else {
                echo '<li><a href="login.php">Log In</a></li>';
                echo '<li><a href="signup.php">Sign Up</a></li>';
            }
            ?>
        </ul>
    </div>
    <div class="login-container">
        <img src="image/logo.png" alt="Logo" class="login-logo">
        <h2>Log In</h2><br>
        <form class="login-form" method="post" action="login.php" novalidate>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" class="logininput" required>
            <span class="error"><?= $usernameInvalid ?></span>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="logininput" required>
            <span class="error"><?= $passwordInvalid ?></span>

            <br><p>Don't Have an Account? <a href="signup.php" style="text-decoration: underline;">Create Account Here</a>.</p>
            <button type="submit" class="loginbutton" name="login"><b>Login</b></button>
        </form>
    </div>
</body>
</html>
