<?php

function getQueueRemainingTime() {
	global $db, $queueWait;
	$sql = $db->prepare("
		SELECT
			$queueWait-TIMESTAMPDIFF(MINUTE, added, NOW()) as remaining
		FROM
			queue
		WHERE
			ip=INET_ATON(?) AND added > DATE_SUB(NOW(), INTERVAL $queueWait MINUTE)
		ORDER BY
			added DESC
		LIMIT 100");
	$sql->execute(array($_SERVER['REMOTE_ADDR']));
	$resultQueue = $sql->fetchAll();
	//echo "<pre>";print_r($resultQueue);echo "</pre>";
	$remaining = isset($resultQueue[0]) ? ceil($resultQueue[0]['remaining']) : 0;
	return $remaining;
}

function getTotalTrackCount() {
	global $db;
	$sql = $db->prepare("
		SELECT
			COUNT(tracks.id) as trackCount
		FROM
			tracks");
	$sql->execute();
	return intval($sql->fetch()['trackCount']);
}