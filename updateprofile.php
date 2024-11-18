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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $additionalInfo = $_POST['additional_info'];
    
    // Update user information in the database
    try {
        $stmt = $db->prepare("UPDATE users SET additional_info = :additional_info WHERE user_id = :id");
        $stmt->bindParam(':additional_info', $additionalInfo);
        $stmt->bindParam(':id', $userID);
        $stmt->execute();
        
        // Redirect back to the profile page with a success message
        header("Location: userprofile.php?update=success");
        exit();
    } catch (PDOException $e) {
        echo "Error updating profile: " . $e->getMessage();
        exit;
    }
}

// Close the database connection
$db = null;
?>
