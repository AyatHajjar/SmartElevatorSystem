//Authenticate, currently unused
<?php 
	$submitted = !empty($_POST);	 // Form submit sucessful if POST array not empty  
	if ($submitted == 1) {			 // If user acceses page for first time
		$username = $_POST['username'];  
		$password = $_POST['password'];	 
		setcookie('username', $username);
		setcookie('password', $password);
	} else {						 // After first login get, login info from $_COOKIE array
		$username = $_COOKIE['username'];
		$password = $_COOKIE['password'];
	}	
	
	echo "<p>Form sumitted sucessfully (1 for true): $submitted </p>"; 
	echo "<p>Username received: $username </p>"; 
	echo "<p>Password received: $password </p>"; 
?>
