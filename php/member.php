<?php
	session_start();
	
	if(isset($_SESSION['username'])){
		echo '<p>Memebers Only</p>';
		echo '<p><a href="index.php">Elevator</a></p>';
		echo '<p><a href="logout.php">logout</a></p>';
	
	} else {
	 	echo '<p>Not authorized</p>';
	}
?>
