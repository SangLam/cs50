<!DOCTYPE html>

<html>

	<head>
		<meta charset="UTF-8">
	
		<!-- styles -->
	    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous"/>
		
		<link rel="stylesheet" href="/css/style.css"/>
		
		<!-- javascript -->
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>

		<script src="/js/gtd.js"></script>
		
		<!-- header -->
		<?php if (isset($title)): ?>
			<title>CS50 Project: <?= htmlspecialchars($title) ?></title>
		<?php else: ?>
			<title>CS50 Project</title>
		<?php endif ?>
	</head>

	<body>