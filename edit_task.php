<?php
require('db_connect.php');

session_start();

if (isset($_GET['task_id'])) {
    $task_id = mysqli_real_escape_string($dbc, $_GET['task_id']);
    $task_query = "SELECT * FROM tasks WHERE task_id = '$task_id'";
    $task_result = mysqli_query($dbc, $task_query);
    $task_data = mysqli_fetch_assoc($task_result);
    
    $parts_query = "SELECT * FROM parts WHERE task_id = '$task_id'";
    $parts_result = mysqli_query($dbc, $parts_query);
    $parts_data = [];
    while ($part = mysqli_fetch_assoc($parts_result)) {
        $parts_data[] = $part;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_task'])) {
        // Delete uploads associated with the task
        $delete_uploads_query = "DELETE FROM uploads WHERE task_id = '$task_id'";
        if (!mysqli_query($dbc, $delete_uploads_query)) {
            echo '<div class="alert alert-danger">Error deleting uploads: ' . mysqli_error($dbc) . '</div>';
            exit();
        }
        
        // Delete parts associated with the task
        $delete_parts_query = "DELETE FROM parts WHERE task_id = '$task_id'";
        if (!mysqli_query($dbc, $delete_parts_query)) {
            echo '<div class="alert alert-danger">Error deleting parts: ' . mysqli_error($dbc) . '</div>';
            exit();
        }
        
        // Delete the task itself
        $delete_task_query = "DELETE FROM tasks WHERE task_id = '$task_id'";
        if (mysqli_query($dbc, $delete_task_query)) {
            header("Location: task.php?success=task_deleted");
            exit();
        } else {
            echo '<div class="alert alert-danger">Error deleting task: ' . mysqli_error($dbc) . '</div>';
        }
    } else {
        $task_name = mysqli_real_escape_string($dbc, $_POST['task_name']);
        $description = mysqli_real_escape_string($dbc, $_POST['description']);
        $due_date = mysqli_real_escape_string($dbc, $_POST['due_date']);
        
        $task_query = "UPDATE tasks SET task_name = '$task_name', description = '$description', due_date = '$due_date' WHERE task_id = '$task_id'";
        if (mysqli_query($dbc, $task_query)) {
            foreach ($_POST['part_name'] as $index => $part_name) {
                $part_id = mysqli_real_escape_string($dbc, $_POST['part_id'][$index]);
                $part_name = mysqli_real_escape_string($dbc, $part_name);
                $part_description = mysqli_real_escape_string($dbc, $_POST['part_description'][$index]);
                
                if (!empty($part_id)) {
                    $part_query = "UPDATE parts SET part_name = '$part_name', description = '$part_description' WHERE part_id = '$part_id'";
                } else {
                    $part_query = "INSERT INTO parts (task_id, part_name, description) VALUES ('$task_id', '$part_name', '$part_description')";
                }
                if (!mysqli_query($dbc, $part_query)) {
                    echo '<div class="alert alert-danger">Error updating parts: ' . mysqli_error($dbc) . '</div>';
                    exit();
                }
            }
            
            if (!empty($_POST['delete_part'])) {
                foreach ($_POST['delete_part'] as $delete_part_id) {
                    $delete_part_id = mysqli_real_escape_string($dbc, $delete_part_id);
                    
                    // Delete uploads associated with the part
                    $delete_uploads_query = "DELETE FROM uploads WHERE part_id IN (SELECT part_id FROM parts WHERE part_id = '$delete_part_id')";
                    if (!mysqli_query($dbc, $delete_uploads_query)) {
                        echo '<div class="alert alert-danger">Error deleting uploads: ' . mysqli_error($dbc) . '</div>';
                        exit();
                    }
                    
                    // Delete the part itself
                    $delete_part_query = "DELETE FROM parts WHERE part_id = '$delete_part_id'";
                    if (!mysqli_query($dbc, $delete_part_query)) {
                        echo '<div class="alert alert-danger">Error deleting part: ' . mysqli_error($dbc) . '</div>';
                        exit();
                    }
                }
            }
            
            header("Location: edit_task.php?task_id=$task_id&success=true");
            exit();
        } else {
            echo '<div class="alert alert-danger">Error updating task: ' . mysqli_error($dbc) . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
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
    padding-bottom: 20px; /* Adjust the value as needed */
}
  /* Added styles for 'Edit Task' button and input/button elements */
  .btn-primary {
    display: block;
    width: 50%;
    margin: 20px auto 20px auto; /* Adjusted bottom margin */
    padding: 10px 0;
    font-size: 1.5em;
  }
  input, button {
    color: initial;
  }
  
  .btn-secondary{
    width: 105px;
  }

  #deletetask {
    display: block;
    width: 50%;
    margin: 0 auto; /* Center the delete button */
    padding: 10px 0;
    font-size: 1.5em;
  }
</style>
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
                    echo '<li><a href="logout.php">Logout</a></li>';
                } else {
                    echo '<li><a href="login.php">Log In</a></li>';
                    echo '<li><a href="signup.php">Sign Up</a></li>';
                    header("Location: login.php");
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="back-button">
        <a href="task_info.php?task_id=<?php echo $task_id; ?>">&larr; Back</a>
    </div>
    <div class="container mt-5">
        <h2>Edit Task</h2>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
            <div class="alert alert-success">Task and parts updated successfully.</div>
        <?php endif; ?>
        <form id="edit_task_form" action="edit_task.php?task_id=<?php echo $task_id; ?>" method="POST">
            <div class="form-group">
                <label for="task_name">Task Name</label>
                <input type="text" class="form-control" id="task_name" name="task_name" value="<?php echo htmlspecialchars($task_data['task_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($task_data['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="datetime-local" class="form-control" id="due_date" name="due_date" value="<?php echo isset($task_data['due_date']) ? date('Y-m-d\TH:i', strtotime($task_data['due_date'])) : ''; ?>" required>
            </div>
            <div class="parts-section">
                <h3>Parts</h3>
                <div id="parts_container">
                    <?php foreach ($parts_data as $index => $part): ?>
                        <div class="part">
                            <input type="hidden" name="part_id[]" value="<?php echo htmlspecialchars($part['part_id']); ?>">
                            <div class="form-group">
                                <label for="part_name_<?php echo $index; ?>">Part Name</label>
                                <input type="text" class="form-control" id="part_name_<?php echo $index; ?>" name="part_name[]" value="<?php echo htmlspecialchars($part['part_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="part_description_<?php echo $index; ?>">Description</label>
                                <textarea class="form-control" id="part_description_<?php echo $index; ?>" name="part_description[]" required><?php echo htmlspecialchars($part['description']); ?></textarea>
                            </div>
                            <button type="button" class="btn btn-danger" onclick="deletePart(this)">Delete Part</button>
                            <input type="hidden" name="delete_part[]" value="">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addPart()">Add Part</button>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
        <form action="edit_task.php?task_id=<?php echo $task_id; ?>" method="POST" onsubmit="return confirmDeleteTask();">
            <input type="hidden" name="delete_task" value="1">
            <button type="submit" class="btn btn-danger" id="deletetask">Delete Task</button>
        </form>
    </div>

    <script>
        function addPart() {
            var partsContainer = document.getElementById('parts_container');
            var index = partsContainer.children.length;
            var partDiv = document.createElement('div');
            partDiv.className = 'part';
            partDiv.innerHTML = `
                <input type="hidden" name="part_id[]" value="">
                <div class="form-group">
                    <label for="part_name_${index}">Part Name</label>
                    <input type="text" class="form-control" id="part_name_${index}" name="part_name[]" required>
                </div>
                <div class="form-group">
                    <label for="part_description_${index}">Description</label>
                    <textarea class="form-control" id="part_description_${index}" name="part_description[]" required></textarea>
                </div>
                <button type="button" class="btn btn-danger" onclick="deletePart(this)">Delete Part</button>
                <input type="hidden" name="delete_part[]" value="">
            `;
            partsContainer.appendChild(partDiv);
        }

        function deletePart(button) {
            if (confirm('Are you sure you want to delete this part?')) {
                var partDiv = button.parentNode;
                var deleteInput = partDiv.querySelector('input[name="delete_part[]"]');
                deleteInput.value = partDiv.querySelector('input[name="part_id[]"]').value;
                partDiv.style.display = 'none';
            }
        }

        function confirmDeleteTask() {
            return confirm('Are you sure you want to delete this task?');
        }
    </script>
</body>
</html>
