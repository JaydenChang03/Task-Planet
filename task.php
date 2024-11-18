<?php
// Include the database connection file
require('db_connect.php');

session_start(); // Start the session to access session variables

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

$userid = $_SESSION['userid'];
$username = $_SESSION['username'];

// Retrieve tasks associated with the user as both the leader and assigned user
$tasks_query = "SELECT t.task_id, t.task_name, t.created_at, t.due_date,
                (SELECT COUNT(p1.part_id) FROM parts p1 WHERE p1.task_id = t.task_id) AS total_parts,
                (SELECT COUNT(p2.part_id) FROM parts p2 WHERE p2.task_id = t.task_id AND p2.done = 1) AS completed_parts
                FROM tasks t
                LEFT JOIN parts p ON t.task_id = p.task_id
                WHERE t.leader = '$userid' OR p.user_id = '$userid'
                GROUP BY t.task_id, t.task_name, t.created_at, t.due_date";
$tasks_result = mysqli_query($dbc, $tasks_query);

if (!$tasks_result) {
    echo 'Error retrieving tasks: ' . mysqli_error($dbc);
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/task.css">
    <style>
        .progress-circle {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #f2f2f2;
            margin: 0 auto;
            overflow: hidden;
        }

        .progress-circle::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: conic-gradient(#4caf50 0%, #4caf50 calc(var(--progress) * 100%), #ffffff calc(var(--progress) * 100%), #ffffff 100%);
            transform: rotate(-90deg);
        }

        .progress-circle .progress-text {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            text-align: center;
            transform: translateY(-50%);
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="banner">
        <div class="navbar">
            <a href="home.php">
                <img src="image/logo.png" class="logo" alt="Logo">
            </a>
            <ul>
                <?php
                // Check if userid is set in session
                if (isset($_SESSION['userid'])) {
                    // User is logged in
                    echo '<li><a href="userprofile.php">Profile</a></li>';
                    echo '<li><a href="logout.php">Logout</a></li>'; // Logout link
                } else {
                    // User is not logged in
                    echo '<li><a href="login.php">Log In</a></li>';
                    echo '<li><a href="signup.php">Sign Up</a></li>';
                    header("Location: login.php");
                }
                ?>
            </ul>
        </div>

        <div class="content">
            <div class="task-info">
                <h2>Your Tasks</h2>
            </div>
            <div class="task-grid">
                <?php while ($task = mysqli_fetch_assoc($tasks_result)) { ?>
                    <div class="task-box">
                        <h4><?php echo htmlspecialchars($task['task_name']); ?></h4>
                        <p><strong>Created At:</strong> <?php echo htmlspecialchars($task['created_at']); ?></p>
                        <p><strong>Due Date:</strong> <?php echo htmlspecialchars($task['due_date']); ?></p>
                        <div class="progress-circle" style="--progress: <?php echo ($task['completed_parts'] / $task['total_parts']); ?>">
                            <span class="progress-text"><?php echo $task['completed_parts'] . ' out of ' . $task['total_parts'] . ' parts done'; ?></span>
                        </div>
                        <a href="task_info.php?task_id=<?php echo $task['task_id']; ?>" class="view-button">View Details</a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
