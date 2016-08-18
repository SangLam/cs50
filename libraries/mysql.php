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

		private static $handle;
		
		public static function init($path) {
			if (!is_file($path)) {
				trigger_error("Could not find {$path}", E_USER_ERROR);
			}

			$contents = file_get_contents($path);
			if ($contents === false) {
				trigger_error("Could not read {$path}", E_USER_ERROR);
			}

			$config = json_decode($contents, true);
			if (is_null($config))
			{
				trigger_error("Could not decode {$path}", E_USER_ERROR);
			}
			 
			foreach (["servername", "name", "password", "username"] as $key)
			{
				if (!isset($config["database"][$key]))
				{
					trigger_error("Missing value for database.{$key}", E_USER_ERROR);
				}
			}

			self::$servername = $config['database']['servername'];
			self::$name = $config['database']['name'];
			self::$username = $config['database']['username'];
			self::$password = $config['database']['password'];
		}
		
		private static function getHandle() {
			if (!isset(self::$handle)) {
				try {
					self::$handle = new PDO("mysql:host=" . self::$servername . ";dbname=" . self::$name, self::$username, self::$password);
				
					// set the PDO error mode to exception
					self::$handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
				catch(PDOException $e) {
					echo "Connection failed: " . $e->getMessage();
				}
			}
			
			return self::$handle;
		}
			
		public static function queryUsers($sql, $args) {
			$args += [
				':id' => null,
				':username' => null,
				':hash' => null,
				':firstname' => null,
				':lastname' => null,
				':email' => null,
				':token' => null,
				':tokenExpiration' => null,
				':ENUserId' => null,
				':webApiUrlPrefix' => null,
				':taskFolderGuid' => null,
				':taskFolderName' => null,
				':projectStack' => null,
				':tags' => null
			];
			
			$integers =  [':id', ':tokenExpiration'];
			$userStrings = [':username', ':firstname', ':lastname'];
			$hash = [':hash'];
			$emails = [':email'];
			$evernoteStrings = [':token', ':ENUserId', ':taskFolderGuid', ':taskFolderName', ':projectStack'];
			$evernoteUrl = [':webApiUrlPrefix'];
			$tags = [':tags'];
			
			$statement = self::gethandle()->prepare($sql);
			
			self::bindInt($args, $integers, $statemnt);
			self::bindUserStrings($args, $userStrings, $statement);
			self::bindHash($args, $hash, $statement);
			self::bindEmail($args, $emails, $statement);
			self::bindEvernoteStrings($args, $evernoteStrings, $statement);
			self::bindEvernoteUrl($args, $evernoteUrl, $statement);
			self::bindTags($args, $tags, $statemnt);
			
			//print $statement->debugDumpParams();
			try {
				$statement->execute();
			}
			catch (PDOException $e) {
				var_dump( $e->getMessage());
			}
			
			if ($statement->columnCount() > 0)
			{
				// return result set's rows
				return $statement->fetchAll(PDO::FETCH_ASSOC);
			}

			// if query was DELETE, INSERT, or UPDATE
			else
			{
				// return number of rows affected
				return $statement->rowCount();
			}
		}
		
		/* public static function queryNotes($sql, $args) {
			$args += [
				':userId'
		} */
		
		private static function bindInt($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$var = filter_var($args[$key], FILTER_SANITIZE_NUMBER_INT);
					$statement->bindValue($key, $var, PDO::PARAM_INT);
				}
			}
		}
		
		private static function bindUserStrings($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$var = filter_var($args[$key], FILTER_SANITIZE_STRING);
					$statement->bindValue($key, $var, PDO::PARAM_STR);
				}
			}
			
		}
		
		private static function bindHash($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$statement->bindValue($key, $args[$key]);
				}
			}
		}
		
		private static function bindEmail($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$var = filter_var($args[$key], FILTER_SANITIZE_EMAIL);
					$statement->bindValue($key, $args[$key], PDO::PARAM_STR);
				}
			}
		}
		
		private static function bindEvernoteStrings($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$var = filter_var($args[$key], FILTER_SANITIZE_STRING);
					$statement->bindValue($key, $args[$key], PDO::PARAM_STR);
				}
			}
		}
		
		private static function bindEvernoteUrl ($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$var = filter_var($args[$key], FILTER_SANITIZE_URL);
					$statement->bindValue($key, $var);
				}
			}
		}
		
		private static function bindTags($args, $keys, &$statement) {
			foreach ($keys as $key) {
				if (isset($args[$key])) {
					$statement->bindValue($key, $args[$key]);
				}
			}
		}
	}
	
	

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	