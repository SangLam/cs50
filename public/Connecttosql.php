<?php
	 
	 // try to connect to database
	 $servername = "localhost";
	 $username = "root";
	 $password = "siarlftsm@ICl";
	 
	static $handle;
	if (!isset($handle)) {
		try
		{
			// connect to database
			$handle = new PDO(
				"mysql:host=$servername;dbname=users",
				$username, $password
			);
		}
		catch (Exception $e)
		{
			// trigger (big, orange) error
			echo "connection failed: " . ($e->getMessage());
		}
	}
	