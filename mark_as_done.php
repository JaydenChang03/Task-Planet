<?php
// Include the database connection file
require('db_connect.php');

session_start(); // Start the session to access session variables

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Ensure part ID is provided
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['part_id'])) {
    $part_id = (int)$_POST['part_id'];
    
    // Update the 'done' attribute for the part
    $update_query = "UPDATE parts SET done = 1 WHERE part_id = $part_id AND user_id = {$_SESSION['userid']}";
    if (mysqli_query($dbc, $update_query)) {
        echo '<div class="alert alert-success">Part marked as done successfully.</div>';
    } else {
        echo '<div class="alert alert-danger">Error updating part: ' . mysqli_error($dbc) . '</div>';
    }
}

// Redirect back to the task info page
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
header("Location: task_info.php?task_id=$task_id");
exit();
?>
