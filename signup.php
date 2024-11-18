<?php
session_start();
require('db_connect.php');

$emailError = "";
$usernameError = "";
$passwordError = "";
$signupSuccess = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty($_POST['email'])) {
        $emailError = "Email is required";
    } else {
        $email = $_POST['email'];
        // Check if email format is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailError = "Invalid email format";
        } elseif (!preg_match("/@.*\.com$/", $email)) {
            $emailError = "Email must end with '.com'";
        } else {
            // Check if email already exists
            $query = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($dbc, $query);
            if (mysqli_num_rows($result) > 0) {
                $emailError = "Email already exists";
            }
        }
    }
    
    // Validate username
    if (empty($_POST['username'])) {
        $usernameError = "Username is required";
    } else {
        $username = $_POST['username'];
        // Check if username already exists
        $query = "SELECT * FROM users WHERE user_name = '$username'";
        $result = mysqli_query($dbc, $query);
        if (mysqli_num_rows($result) > 0) {
            $usernameError = "Username already exists";
        }
    }
    
    // Validate password
    if (empty($_POST['password'])) {
        $passwordError = "Password is required";
    } else {
        $password = $_POST['password'];
        // Password must have 1 uppercase letter, 1 number, and minimum 8 characters
        if (!preg_match("/^(?=.*[A-Z])(?=.*[0-9]).{8,}$/", $password)) {
            $passwordError = "Password must contain at least one uppercase letter, one number, and be at least 8 characters long";
        }
    }
    
    // If no errors, insert into database
    if (empty($emailError) && empty($usernameError) && empty($passwordError)) {
        $query = "INSERT INTO users (user_name, password, email) VALUES ('$username', '$password', '$email')";
        if (mysqli_query($dbc, $query)) {
            $signupSuccess = "Sign up successful! You can now <a href='login.php'>Login</a>.";
        } else {
            echo "Error: " . mysqli_error($dbc);
        }
    }
}

mysqli_close($dbc);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/signupstyle.css">
    <title>Sign Up</title>
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
    <div class="signup-container">
        <h2>Sign Up</h2><br>
        <form class="signup-form" method="POST" action="signup.php" novalidate>
            <label for="email">Create new Email:</label>
            <input type="email" id="email" name="email" class="signup-input" required>
            <span class="error"><?= $emailError ?></span>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" class="signup-input" required>
            <span class="error"><?= $usernameError ?></span>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="signup-input" required>
            <span class="error"><?= $passwordError ?></span>

            <label for="passwordrequire">Password must contain at least:<br>1 Uppercase Letter<br>1 Number<br>8 Characters</label>

            <button type="submit" class="signup-button"><b>Sign Up</b></button>
        </form>

        <br><p class="signup-success"><?= $signupSuccess ?></p>

        <br><p>Already have an account? <a href="login.php">Login here</a>.</p>
    </div>
</div>
</body>
</html>
