<?php
session_start();
// Database connection parameters.
$host = 'localhost';
$dbname = 'taskplanet';
$user = 'root';
$password = '';
$attr = "mysql:host=$host;dbname=$dbname";
$table = 'users';

// Attempt to establish a PDO database connection.
try {
    $db = new PDO($attr, $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}

// Check if a user session is active; redirect to login if not.
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Fetch user information based on the session's user ID.
$userID = $_SESSION['userid'];
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :userid");
    $stmt->bindParam(':userid', $userID);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If the user is not found, display an error message and exit.
    if (!$user) {
        echo "User not found.";
        exit;
    }
    $username = $user['user_name'];
    $email = $user['email'];
    $password = $user['password'];
    $additionalInfo = isset($user['additional_info']) ? $user['additional_info'] : '';
} catch (PDOException $e) {
    echo "Error fetching user information: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="css/userprofilestyle.css">
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
                    if (isset($_SESSION['admin_status']) && $_SESSION['admin_status'] == 1) {
                        echo '<li><a href="admin/adminpage.php">Admin</a></li>';
                    }
                    echo '<li><a href="logout.php">Logout</a></li>';
                } else {
                    echo '<li><a href="login.php">Log In</a></li>';
                    echo '<li><a href="signup.php">Sign Up</a></li>';
                }
                ?>
            </ul>
        </div>
    
    <div class="profile-section"> 
        <div class="bannerprofile">
            <p><h2>User Profile</h2></p>
        </div> 
        <form method="post" action="updateprofile.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>" required>

            <label for="additional_info">Additional Info:</label>
            <textarea id="additional_info" name="additional_info"><?php echo htmlspecialchars($additionalInfo); ?></textarea>

            <div class="form-buttons">
                <a href="home.php">
                    <button type="button" class="back-button">Back</button>
                </a>
                <button type="submit">Update Profile</button>
            </div>
        </form>
    </div>
    </div>
</body>
</html>
