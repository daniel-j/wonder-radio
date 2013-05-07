<?php
require_once "config.php";

if (isset($_GET['logout'])) {
	unset($_SESSION['state']);
	header("Location: ./");
	exit;
}

if (isset($_POST['pswd']) && $_POST['pswd'] === $adminPass) {
	$state['admin'] = true;
}

if ($state['admin']) {

	if (isset($_GET['next'])) {
		exec("mpc -h $mpdPassword@:: next -q --wait");
		sleep(2);
	}
	
	header("Location: ./");
	exit;
}

?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Radio Admin</title>
</head>
<body>

<?php

if (!$state['admin']) {
	?>

<form method="post">
	<h3>Admin login</h3>
	<input type="password" name="pswd" autofocus>
	<input type="submit" value="Login">
</form>

	<?php
} else {
	?>
	You are logged in.
	<?php
}

?>

</body>
</html>