<?php 
	require '../libraries/tasks.php'; 
	
	$token = 'S=s1:U=9265e:E=15b96082f78:C=1543e570150:P=1cd:A=en-devtoken:V=2:H=b3713272a2446d768ff6b5764c1bea5c';
	$sandbox = true;
	$china   = false;
	
	Evernote::init($token, $sandbox, $china);
	 
	Evernote::getTitleWithTerm('New note');