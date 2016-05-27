<?php

		/*
			a data class http://phpbridge.org/intro-to-php/creating_a_data_class

			http://www.phptherightway.com/#pdo_extension
			use pdo class http://php.net/book.pdo
			function filter_input
			function bindParam*/
	class mysql {
		private static $servername;
		private static $username;
		private static $password;
		
		static $handle;
		
		public static function init($servername, $username, $password) {
			self::$servername = $servername;
			self::$username = $username;
			self::$password = $password;
		}
			

		public static function getHandle() {
			try {
				self::$handle = new PDO("mysql:host=" . self::$servername . ";dbname=gtd", self::$username, self::$password);
			
				// set the PDO error mode to exception
				self::$handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				echo "Connected successfully"; 
				}
			catch(PDOException $e) {
				echo "Connection failed: " . $e->getMessage();
				}
		}
	}

?>