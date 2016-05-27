<?php

	//testisg purpose only, remove following lines for production
	require_once("/../includes/config.php");	
	require __DIR__ . '/../vendors/evernote/vendor/autoload.php';

	$token = 'S=s1:U=9265e:E=15b96082f78:C=1543e570150:P=1cd:A=en-devtoken:V=2:H=b3713272a2446d768ff6b5764c1bea5c';
	$sandbox = true;
	$china   = false;
	$client = new \Evernote\Client($token, $sandbox, null, null, $china);
	$advanceClient = $client->getAdvancedClient();
	$noteStore = $advanceClient->getNoteStore();
	
	$notebooks = $client->listNotebooks();

	// extracting notebook that has tasks
	$actionNotebook;
	foreach ($notebooks as $notebook)
		if ($notebook->name == 'First Notebook')
			$actionNotebook = $notebook;

	$tags = getTags($actionNotebook->guid);
	
	$taskTags = array();
	foreach ($tags as $tag) 
		if (!preg_match('/\!\d\-[a-z]+/', $tag->name)) 
			array_push($taskTags, $tag);
		
	echo count($taskTags);
		
	foreach ($taskTags as $tag)
		echo ($tag->name);
			
	
	function getTags($Notebook = null){
		global $noteStore;
		if ($Notebook != null)		
			return $noteStore->listTagsByNotebook($Notebook);
		else
			return $noteStore->listTags();
	}
