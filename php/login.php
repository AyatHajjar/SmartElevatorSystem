<?php
// Check if data was actually posted to this page
$submitted = !empty($_POST);

// Safe fallback variables to prevent "Undefined index" errors
$username = $submitted ? htmlspecialchars($_POST['username']) : 'Not provided';
$password = $submitted ? htmlspecialchars($_POST['password']) : 'Not provided';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Form Handler Page</title>
  </head>
  
  <body>
    <p>Form submitted: <?php echo (int) $submitted; ?></p>
    
    <ul>
      <li><b>Username:</b> <?php echo $username; ?></li>
      <li><b>Password:</b> <?php echo $password; ?></li>
    </ul>
  </body>
</html>
