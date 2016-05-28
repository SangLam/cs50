<?php
	require '../includes/config.php';
	
	$contents = file_get_contents($configFile);
	if ($contents === false) {
		trigger_error("Could not read {$configFile}", E_USER_ERROR);
	}

	$config = json_decode($contents, true);
	if (is_null($config)) {
		trigger_error("Could not decode {$configFile}", E_USER_ERROR);
	}
	
	$oauth_handler = new \Evernote\Auth\OauthHandler($sandbox, false, $china);

	$callback = 'http://' . $config['evernoteApi']['servername'] . '/' . basename(__FILE__);

	try {
		$oauth_data  = $oauth_handler->authorize($config['evernoteApi']['apiKey'], $config['evernoteApi']['apiSecret'], $callback);
	} 
	catch (Evernote\Exception\AuthorizationDeniedException $e) {
		render(declinedAuthentication.php);
	}
	
	/* TODO send data to database and put into $_SESSION */
	
	
