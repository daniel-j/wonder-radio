<?php
error_reporting(-1);
require_once "config.php";

if (!$state['admin']) {
	echo "You are not logged in.";
	exit;
}

if (isset($_GET['truncate'])) {
	$sql = $db->prepare("TRUNCATE TABLE tracks");
	$sql->execute();
	$sql = $db->prepare("TRUNCATE TABLE queue");
	$sql->execute();
	$sql = $db->prepare("TRUNCATE TABLE votes");
	$sql->execute();
	header("Location: update.php");
	exit;
}

exec("mpc -h $mpdPassword@:: update --wait -q");

exec("mpc -h $mpdPassword@:: listall -f %file%@@%title%@@%artist%@@%album%@@%time% -q", $rawlist);

$sqlTracks = $db->prepare("DELETE FROM tracks WHERE id = ?");
$sqlQueue = $db->prepare("DELETE FROM queue WHERE trackId = ?");

$list = array();
$filenames = array();

shuffle($rawlist);

foreach ($rawlist as $rawline) {
	$ok = true;
	$line = explode("@@", $rawline);
	if (count($line) == 5) {
		$line[4] = array_reverse(explode(":", $line[4]));
		$time = 0;
		foreach ($line[4] as $i => $n) {
			$t = intval($n);
			switch ($i) {
				case 0:
					$time += $t;
					break;
				case 1:
					$time += $t*60;
					break;
				case 2:
					$time += $t*60*60;
					break;
				default:
					echo "Track too long:";
					print_r($line);
					exit;

			}
		}
		if ($ok) {
			$line[4] = $time;

			$list[] = $line;
			$filenames[] = $line[0];
		}
	}
}



?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>Update Music Database</title>
</head>
<body>
<h1>Update database</h1>
<?php


$sql = $db->prepare("SELECT id, file FROM tracks");
$sql->execute();
$existing = $sql->fetchAll();

foreach ($existing as $track) {
	if (!in_array($track['file'], $filenames)) {
		echo "Deleted ".$track['file']."<br>";
		$sqlTracks->execute(array($track['id']));
		$sqlQueue->execute(array($track['id']));
	}
}


$sql = $db->prepare("INSERT INTO tracks (file, title, artist, album, tags, time) VALUES(?,?,?,?,?,?)");

foreach ($list as $metadata) {
	$tags = preg_replace('~[\W\_]~', ' ', basename($metadata[0], pathinfo($metadata[0], PATHINFO_EXTENSION))." ".$metadata[1]." ".$metadata[2]);
	
	@$sql->execute(array($metadata[0], $metadata[1], $metadata[2], $metadata[3], $tags, $metadata[4]));
	if (intval($sql->errorCode()) === 0) {
		echo "Added ".$metadata[0]."<br>";
	}
}

echo "Database updated";
?>
<br>
<a href="update.php?truncate" onclick="return confirm('Are you really sure you want to empty the database?');">Empty database and import everything again</a>
</body>
</html>

