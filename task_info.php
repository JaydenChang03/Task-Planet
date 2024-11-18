<?php
// Include the database connection file
require('db_connect.php');

session_start(); // Start the session to access session variables

// Ensure user is logged in
if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

// Get task ID from query parameter
$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;

// Fetch task details
$task_query = "SELECT t.task_name, t.description, t.due_date, u.user_name AS leader_name, t.leader
               FROM tasks t
               JOIN users u ON t.leader = u.user_id
               WHERE t.task_id = $task_id";
$task_result = mysqli_query($dbc, $task_query);
if (!$task_result || mysqli_num_rows($task_result) == 0) {
    echo '<div class="alert alert-danger">Task not found.</div>';
    exit();
}
$task = mysqli_fetch_assoc($task_result);

// Fetch parts for the task, sorted by latest upload version
$parts_query = "SELECT p.part_id, p.part_name, p.description, p.done, u.user_name, u.user_id AS assigned_userid,
                      MAX(IFNULL(up.version, 0)) AS latest_version
               FROM parts p
               LEFT JOIN users u ON p.user_id = u.user_id
               LEFT JOIN uploads up ON p.part_id = up.part_id
               WHERE p.task_id = $task_id
               GROUP BY p.part_id
               ORDER BY latest_version DESC";
$parts_result = mysqli_query($dbc, $parts_query);

$isLeader = $_SESSION['userid'] == $task['leader'];

// Handle form submission to assign user to part
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    $part_id = (int)$_POST['part_id'];
    $user_name = mysqli_real_escape_string($dbc, $_POST['user_name']);
    
    // Check if the entered user name exists in the database
    $user_query = "SELECT user_id FROM users WHERE user_name = '$user_name'";
    $user_result = mysqli_query($dbc, $user_query);
    if (!$user_result || mysqli_num_rows($user_result) == 0) {
        echo '<div class="alert alert-danger">Invalid user name.</div>';
    } else {
        $user = mysqli_fetch_assoc($user_result);
        $userid = $user['user_id'];
        
        $assign_query = "UPDATE parts SET user_id = $userid WHERE part_id = $part_id";
        if (mysqli_query($dbc, $assign_query)) {
            echo '<div class="alert alert-success">User assigned successfully.</div>';
            
            // Reload parts data after assignment
            $parts_result = mysqli_query($dbc, $parts_query);
        } else {
            echo '<div class="alert alert-danger">Error assigning user: ' . mysqli_error($dbc) . '</div>';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_file'])) {
    $part_id = (int)$_POST['part_id'];
    $userid = $_SESSION['userid'];
    $description = mysqli_real_escape_string($dbc, $_POST['description']); // Escape description for security
    
    // Verify if part_id exists
    $verify_part_query = "SELECT COUNT(*) AS count FROM parts WHERE part_id = $part_id";
    $verify_part_result = mysqli_query($dbc, $verify_part_query);
    $verify_part = mysqli_fetch_assoc($verify_part_result);
    
    if ($verify_part['count'] == 0) {
        echo '<div class="alert alert-danger">Invalid part ID.</div>';
    } else {
        // Fetch the latest version for this part
        $version_query = "SELECT IFNULL(MAX(version), 0) AS max_version FROM uploads WHERE part_id = $part_id";
        $version_result = mysqli_query($dbc, $version_query);
        $version_row = mysqli_fetch_assoc($version_result);
        $new_version = $version_row['max_version'] + 1;
        
        // Continue with file upload and insertion into uploads table
        $allowed_extensions = array('pdf', 'docx');
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed_extensions) === false) {
            echo '<div class="alert alert-danger">Invalid file type. Please upload a PDF or DOCX file.</div>';
        } else {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $upload_path = $upload_dir . basename($file_name);
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $upload_query = "INSERT INTO uploads (part_id, task_id, user_id, version, content, description)
                                 VALUES ($part_id, $task_id, $userid, $new_version, '$upload_path', '$description')";
                if (mysqli_query($dbc, $upload_query)) {
                    echo '<div class="alert alert-success">File uploaded successfully.</div>';
                } else {
                    echo '<div class="alert alert-danger">Error saving file information: ' . mysqli_error($dbc) . '</div>';
                }
            } else {
                echo '<div class="alert alert-danger">Failed to upload file.</div>';
            }
        }
    }
}
// Handle marking part as done
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_done'])) {
    $part_id = (int)$_POST['part_id'];
    
    $mark_done_query = "UPDATE parts SET done = 1 WHERE part_id = $part_id";
    if (mysqli_query($dbc, $mark_done_query)) {
        echo '<div class="alert alert-success">Part marked as done successfully.</div>';
        
        // Reload parts data after marking as done
        $parts_result = mysqli_query($dbc, $parts_query);
    } else {
        echo '<div class="alert alert-danger">Error marking part as done: ' . mysqli_error($dbc) . '</div>';
    }
}

// Handle making changes to a done part
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_changes'])) {
    $part_id = (int)$_POST['part_id'];
    
    $make_changes_query = "UPDATE parts SET done = 0 WHERE part_id = $part_id";
    if (mysqli_query($dbc, $make_changes_query)) {
        echo '<div class="alert alert-success">Part status updated successfully. You can now make changes.</div>';
        
        // Reload parts data after making changes
        $parts_result = mysqli_query($dbc, $parts_query);
    } else {
        echo '<div class="alert alert-danger">Error updating part status: ' . mysqli_error($dbc) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Info</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }

        body {
            background-image: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)), url(image/create_task.png);
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
        .back-button {
  display: inline-block;
  position: relative;
  padding-right: 40px;
  padding-top: 20px;
  margin-left: 100px;
}

.back-button a {
  text-decoration: none;
  color: #ABD8FF;
  text-transform: uppercase;
}

.back-button::after {
  content: '';
  height: 3px;
  width: 0px;
  background: #ABD8FF;
  position: absolute;
  left: 0;
  bottom: 0;
}

.back-button:hover::after {
  width: 60%;
}

        .container {
            margin-top: 30px;
            padding-bottom: 30px;
        }

        .card {
            background: #333333;
            padding: 15px;
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .card h2,
        .card h3,
        .card h4,
        .card h5 {
            color: #ABD8FF;
        }

        .card p {
            margin-bottom: 10px;
        }

        .card-text strong {
            color: yellow;
        }

        .assign-form {
            margin-top: 10px;
        }

        .upload-form {
            margin-top: 10px;
        }

        .upload-form .form-group label {
            color: #ABD8FF;
        }

        .upload-form .form-group input[type="file"] {
            color: white;
            background-color: #555555;
            border: 1px solid #ABD8FF;
            padding: 4px 10px 10px 10px;
            border-radius: 5px;
        }

        .upload-form .form-group textarea {
            background-color: #555555;
            border: 1px solid #ABD8FF;
            color: white;
            padding: 10px;
            border-radius: 5px;
        }

        .upload-form .btn {
            margin-top: 5px;
        }
                                
        .mark-done-button {
            margin-top: 10px;
            background-color: #28a745;
            border: none;
            color: white;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .make-changes-button {
            margin-top: 10px;
            background-color: #ffc107;
            border: none;
            color: black;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .table-dark {
            background-color: #444444;
        }

        .table-dark th,
        .table-dark td {
            border-color: #555555;
        }
    </style>
</head>

<body>
    <div class="banner">
      <div class = "navbar">
      <a href="home.php">
        <img src="image/logo.png" class="logo" alt="Logo"></a>
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
      </div>
      
      <div class="back-button">
      	<a href="task.php">&larr;Back</a>
      </div>

    <div class="container">
        <div class="card">
            <h2 class="card-title"><?php echo $task['task_name']; ?></h2>
            <h3 class="card-subtitle mb-2 text-muted">Leader: <?php echo $task['leader_name']; ?></h3>
            <p class="card-text"><?php echo $task['description']; ?></p>
            <p class="card-text"><strong>Due Date: </strong><?php echo $task['due_date']; ?></p>
        </div>

        <?php while ($part = mysqli_fetch_assoc($parts_result)): ?>
        <div class="card">
            <h4 class="card-title"><?php echo $part['part_name']; ?></h4>
            <p class="card-text"><?php echo $part['description']; ?></p>
            <p class="card-text"><strong>Assigned User: </strong><?php echo $part['user_name'] ? $part['user_name'] : 'Not Assigned'; ?></p>
            <p class="card-text"><strong>Status: </strong><?php echo $part['done'] ? 'Done' : 'In Progress'; ?></p>
            <p class="card-text"><strong>Latest Version: </strong><?php echo $part['latest_version']; ?></p>

            <?php if ($isLeader): ?>
            <form method="POST" class="assign-form">
                <input type="hidden" name="part_id" value="<?php echo $part['part_id']; ?>">
                <div class="form-group">
                    <label for="user_name">Assign User:</label>
                    <input type="text" name="user_name" class="form-control" required>
                </div>
                <button type="submit" name="save_user" class="btn btn-primary">Assign</button>
            </form>
            <?php endif; ?>

            <h5>Uploaded Documents</h5>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Document Name</th>
                            <th>Version</th>
                            <th>Description</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $uploads_query = "SELECT submitted_at, content, version, description FROM uploads WHERE part_id = " . $part['part_id'] . " ORDER BY version DESC";
                        $uploads_result = mysqli_query($dbc, $uploads_query);
                        while ($upload = mysqli_fetch_assoc($uploads_result)): ?>
                        <tr>
                            <td><?php echo $upload['submitted_at']; ?></td>
                            <td><?php echo basename($upload['content']); ?></td>
                            <td><?php echo $upload['version']; ?></td>
                            <td><?php echo $upload['description']; ?></td>
                            <td><a href="<?php echo $upload['content']; ?>" class="btn btn-info" download>Download</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($_SESSION['userid'] == $part['assigned_userid'] && !$part['done']): ?>
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="part_id" value="<?php echo $part['part_id']; ?>">
                <div class="form-group">
                    <label for="file">Upload File:</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" name="upload_file" class="btn btn-success">Upload</button>
            </form>
            <button type="button" class="btn mark-done-button" onclick="document.getElementById('mark_done_form_<?php echo $part['part_id']; ?>').submit();">Mark as Done</button>
            <form method="POST" id="mark_done_form_<?php echo $part['part_id']; ?>">
                <input type="hidden" name="part_id" value="<?php echo $part['part_id']; ?>">
                <input type="hidden" name="mark_done" value="1">
            </form>
            <?php elseif ($part['done']): ?>
            <button type="button" class="btn make-changes-button" onclick="document.getElementById('make_changes_form_<?php echo $part['part_id']; ?>').submit();">Make Changes</button>
            <form method="POST" id="make_changes_form_<?php echo $part['part_id']; ?>">
                <input type="hidden" name="part_id" value="<?php echo $part['part_id']; ?>">
                <input type="hidden" name="make_changes" value="0">
            </form>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
        <?php if ($isLeader): ?>
            <a href="edit_task.php?task_id=<?php echo $task_id; ?>" class="btn btn-warning">Edit Task</a>
        <?php endif; ?>
    </div>
</body>

</html>

