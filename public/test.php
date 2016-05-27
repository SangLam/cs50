<?php

	//testisg purpose only, remove following lines for production
	require_once("/../includes/config.php");	
	require __DIR__ . '/../vendors/evernote/vendor/autoload.php';

	$token = 'S=s1:U=9265e:E=15b96082f78:C=1543e570150:P=1cd:A=en-devtoken:V=2:H=b3713272a2446d768ff6b5764c1bea5c';
	
	$sandbox = true;
	$china   = false;
		
	$client = new \Evernote\Client($token, $sandbox, null, null, $china);
	
	$advanceClient = $client->getAdvancedClient();
	
	$search = new \Evernote\Model\Search('note');
	
	$notebook = NULL;
	
	$scope = 'PERSONAL_SCOPE';
	
	$order = \Evernote\Client::SORT_ORDER_REVERSE | \Evernote\Client::SORT_ORDER_RECENTLY_CREATED;

	$maxResult = 5;

	$results = $client->findNotesWithSearch($search, $notebook, $scope, $order, $maxResult);

	foreach($results as $result) {
		if($result->title == "New note") {
			$noteGuid = $result->guid;
			$note = $client->getUserNoteStore()->getNote($token, $noteGuid, true, false, false, false);
			$string = $note->content;
			//preg_match_all($pattern, $string, $matches, PREG_SET_ORDER);
		}			
	}
	$userInfo = $client->getAdvancedClient()->getUserStore()->getPublicUserInfo('batt76');
	$resGuid = $note->resources[0]->guid;
	$resUrl = $userInfo->webApiUrlPrefix . 'res/' . $resGuid;

	$pattern = '/<en-media[^>].*\/>/' ;
	$replacement = "<img src=$resUrl>";
	?>
	<!DOCTYPE html>
	<html>
	<head>
		<title>Test note</title>
	</head>
	<body>
	<h1>Test Note</h1>
	<?php echo preg_replace($pattern, $replacement, $string);?>
	</body>
	</html>
	
		
	