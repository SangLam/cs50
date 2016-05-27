<!DOCTYPE html>

<html>

	<head>
	    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		
			<title>CS50 Project: Login</title>

	</head>

	<body>

		<form action="login.php" method="post">
			<div class="form-group">
				<label for="inputAccountLogin">Username</label>
				<input id="inputAccountLogin" name="username" class="form-control" placeholder="Username/Email" type="text">
			</div>
			<div class="form-group">
				<label for="inputPassword">Password</label>
				<input id="inputPassword" name="password" class="form-control" placeholder="password" type="password">
			</div>
			<button type="submit class="btn btn-default">Submit</button>
		</form>
		
	</body>
</html>