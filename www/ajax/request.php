<?php
require_once dirname(__DIR__)."/config.php";
require_once dirname(__DIR__)."/funcs.php";

$remaining = getQueueRemainingTime();

if (!isset($_GET['trackId'])) {
	exit;
}

$trackId = intval($_GET['trackId']);
$sql = $db->prepare("SELECT COUNT(*) FROM queue");
$sql->execute();
$rows = $sql->fetchAll();
$qcount = $rows[0][0];

if ($qcount < $queueMaxSize) {
	
	if ($remaining === 0) {
		$sql = $db->prepare("
			SELECT
				tracks.id
			FROM
				tracks
			LEFT JOIN queue ON
				tracks.id=queue.trackId
			WHERE
				tracks.id = ? AND queue.id IS NULL AND (lastplayed < DATE_SUB(NOW(), INTERVAL $trackWait MINUTE) OR lastplayed IS NULL)
			LIMIT
				1");
		$sql->execute(array($trackId));
		if($sql->rowCount() > 0) {
			$sql = $db->prepare("INSERT INTO queue (trackId, ip) VALUES (?,INET_ATON(?))");
			$sql->execute(array($trackId, $_SERVER['REMOTE_ADDR']));

			$sql = $db->prepare("UPDATE tracks SET requests = requests + 1 WHERE id = ?");
			$sql->execute(array($trackId));

			$sql = $db->prepare("SELECT id FROM queue ORDER BY id ASC LIMIT $queueMaxSize, 99999999999999999");
			$sql->execute();
			$rows = $sql->fetchAll();
			foreach ($rows as $row) {
				$sql = $db->prepare("DELETE FROM queue WHERE id = ?");
				$sql->execute(array($row['id']));
			}
		}
		header("Location: ./");
	} else {
		echo "<h2>You must wait ".$remaining." more minute(s) before you can queue another, or until the track you queued has been played</h2>
		<a href=\"./\" onclick=\"history.back(); return false;\">Go back</a><pre>";
	}
} else {
	echo "Queue is full";
}
