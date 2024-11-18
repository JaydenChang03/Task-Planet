<html>
  <head>
    <title>Task Planet</title>
    <link rel="stylesheet" href="css/hstyle.css">
  </head>
  <body>
    <div class="banner">
      <div class = "navbar">
      <a href="home.php">
        <img src="image/logo.png" class="logo" alt="Logo"></a>
        <ul>
        <?php
          session_start();
          require('db_connect.php');

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
    <div class="part1">
      <h1>Welcome to Task Planet!</hi>
      <p>Your Ultimate Tool for Creating <br>and Managing Tasks Effortlessly.</p>
      <div class="homeButton">
      <a href="create_task.php">
        <button type = "button"><span>Create Task</span></button></a>
      <a href="task.php">
        <button type = "button"><span>Manage Task</span ></button></a>
      </div>
    </div>

</html>