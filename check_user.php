<?php
// Include the database connection file
require('db_connect.php');

$user_name = mysqli_real_escape_string($dbc, $_GET['user_name']);

$user_query = "SELECT user_id FROM users WHERE user_name = '$user_name'";
$user_result = mysqli_query($dbc, $user_query);
if ($user_result && mysqli_num_rows($user_result) > 0) {
    // User exists
    echo json_encode(['exists' => true]);
} else {
    // User does not exist
    echo json_encode(['exists' => false]);
}
?>