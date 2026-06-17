//member currently unused
<?php 
	session_start();
	
	if(isset($_SESSION['username'])){
		echo "Welcome, " . $_SESSION['username'] . "!<br />";
		echo "<p>Members only content - for your eyes only</p>";
		echo "Click to <a href='logout.php'>Logout</a>";
	}else{
		echo "<p>You must be logged in!</p>";
	}
?>
