<?php
// Include the database connection file
require('db_connect.php');

error_reporting(0);

session_Start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_name = mysqli_real_escape_string($dbc, $_POST['task_name']);
    $description = mysqli_real_escape_string($dbc, $_POST['description']);
    $due_date = mysqli_real_escape_string($dbc, $_POST['due_date']);
    
    // Get user details from session
    $user_id = $_SESSION['userid'];
    $username = $_SESSION['username'];
    
    // Insert task into the Tasks table with leader information and due date
    $task_query = "INSERT INTO tasks (task_name, description, due_date, leader) VALUES ('$task_name', '$description', '$due_date', '$user_id')";
    if (mysqli_query($dbc, $task_query)) {
        $task_id = mysqli_insert_id($dbc);
        
        // Insert parts into the Parts table
        foreach ($_POST['part_name'] as $index => $part_name) {
            $part_name = mysqli_real_escape_string($dbc, $part_name);
            $part_description = mysqli_real_escape_string($dbc, $_POST['part_description'][$index]);
            
            // Insert part without specifying part_id
            $part_query = "INSERT INTO parts (task_id, part_name, description) VALUES ('$task_id', '$part_name', '$part_description')";
            if (!mysqli_query($dbc, $part_query)) {
                echo '<div class="alert alert-danger">Error inserting parts: ' . mysqli_error($dbc) . '</div>';
                exit();
            }
        }
        
        // Handle file upload if a file is provided
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowedExtensions = ['pdf', 'docx'];
            $file = $_FILES['file'];
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $uploadDirectory = 'uploads/';
                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0777, true);
                }
                
                $uploadFilePath = $uploadDirectory . basename($file['name']);
                if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
                    // Save file metadata to the database
                    $fileName = mysqli_real_escape_string($dbc, $file['name']);
                    $filePath = mysqli_real_escape_string($dbc, $uploadFilePath);
                    $query = "INSERT INTO uploads (part_id, user_id, version, content, submitted_at) VALUES (1, $user_id, 1, '$filePath', NOW())";
                    
                    if (mysqli_query($dbc, $query)) {
                        echo '<div class="alert alert-success">Task, parts, and file uploaded successfully.</div>';
                    } else {
                        echo '<div class="alert alert-danger">Failed to save file metadata to the database.</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">Failed to upload file.</div>';
                }
            } else {
                echo '<div class="alert alert-danger">Invalid file type. Please upload a PDF or DOCX file.</div>';
            }
        } else {
            echo '<div class="alert alert-success">Task and parts created successfully.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Error inserting task: ' . mysqli_error($dbc) . '</div>';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }
        body {
            background-image: linear-gradient(rgba(0,0,0,0.75),rgba(0,0,0,0.75)),url(image/create_task.png);
            color: #ABD8FF; /* Updated text color */
        }
        .navbar {
            width: 100%;
            margin: auto;
            padding: 20px 0;
            align-items: center;
            justify-content: space-between;
            display: flex;
            background-color: #000000;
            background-size: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .logo {
            width: 175px;
            cursor: pointer;
            padding-left: 50px;
        }
        .navbar ul li {
            list-style: none;
            display: inline-block;
            margin: 0 20px;
            position: relative;
            padding-right: 50px;
            padding-top: 20px;
        }
        .navbar ul li a {
            text-decoration: none;
            color: #ABD8FF;
            text-transform: uppercase;
        }
        .navbar ul li::after {
            content: '';
            height: 3px;
            width: 0px;
            background: #ABD8FF;
            position: absolute;
            left: 0;
            bottom: 0;
        }
        .navbar ul li:hover::after {
            width: 60%;
        }
        .container {
            padding-bottom: 20px; /* Adjust the value as needed */
        }
        /* Added styles for 'Create Task' button and input/button elements */
        .btn-primary {
            display: block;
            width: 50%;
            margin: 20px auto 50px auto;
            padding: 10px 0;
            font-size: 1.5em;
        }
        input, button {
            color: initial;
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
                session_start();
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
    </div>
    <div class="container mt-5">
        <h2>Create Task</h2>
        <form id="taskForm" action="create_task.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="task_name">Task Name:</label>
                <input type="text" class="form-control" id="task_name" name="task_name" required>
            </div>
            <div class="form-group">
                <label for="description">Task Description:</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date and Time:</label>
                <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
            </div>
            <div id="partsContainer">
                <h4>Parts</h4>
                <div class="part" id="part_1">
                    <div class="form-group">
                        <label for="part_name_1">Part 1 Name:</label>
                        <input type="text" class="form-control" id="part_name_1" name="part_name[]" required>
                        <label for="part_description_1">Part Description:</label>
                        <textarea class="form-control" id="part_description_1" name="part_description[]" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-secondary" onclick="addPart()">Add Part</button>
                <button type="button" class="btn btn-danger" onclick="removePart()">Remove Part</button>
            </div>
            <button type="submit" class="btn btn-primary">Create Task</button>
        </form>
    </div>

    <script>
        let partCount = 1;

        function addPart() {
            partCount++;
            const partsContainer = document.getElementById('partsContainer');
            const newPartDiv = document.createElement('div');
            newPartDiv.className = 'part';
            newPartDiv.id = 'part_' + partCount;

            newPartDiv.innerHTML = `
                <div class="form-group">
                    <label for="part_name_${partCount}">Part ${partCount} Name:</label>
                    <input type="text" class="form-control" id="part_name_${partCount}" name="part_name[]" required>
                    <label for="part_description_${partCount}">Part Description:</label>
                    <textarea class="form-control" id="part_description_${partCount}" name="part_description[]" rows="2"></textarea>
                </div>
            `;

            partsContainer.appendChild(newPartDiv);
        }

        function removePart() {
            if (partCount > 1) {
                const partsContainer = document.getElementById('partsContainer');
                const partToRemove = document.getElementById('part_' + partCount);
                partsContainer.removeChild(partToRemove);
                partCount--;
            }
        }
    </script>
</body>
</html>

