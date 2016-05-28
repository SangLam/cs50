<?php

		/*
			a data class http://phpbridge.org/intro-to-php/creating_a_data_class

			http://www.phptherightway.com/#pdo_extension
			use pdo class http://php.net/book.pdo
			function filter_input
			function bindParam*/
	class mysql {
		private static $servername;
		private static $name;
		private static $username;
		private static $password;

		static $handle;
		
		public static function init($path) {

            // ensure configuration file exists
            if (!is_file($path))
            {
                trigger_error("Could not find {$path}", E_USER_ERROR);
            }

            // read contents of configuration file
            $contents = file_get_contents($path);
            if ($contents === false)
            {
                trigger_error("Could not read {$path}", E_USER_ERROR);
            }

            // decode contents of configuration file
            $config = json_decode($contents, true);
            if (is_null($config))
            {
                trigger_error("Could not decode {$path}", E_USER_ERROR);
            }

			self::$servername = $config['database']['servername'];
			self::$name = $config['database']['name'];
			self::$username = $config['database']['username'];
			self::$password = $config['database']['password'];
		}
			

		public static function getHandle() {
			try {
				self::$handle = new PDO("mysql:host=" . self::$servername . ";dbname=" . self::$name, self::$username, self::$password);
			
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