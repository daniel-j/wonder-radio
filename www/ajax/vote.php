<?php
require_once dirname(__DIR__)."/config.php";

if (isset($_GET['vote']) && isset($_GET['trackId'])) {
	$vote = intval($_GET['vote']);
	if ($vote >= -1 && $vote <= 1) {
		$trackId = intval($_GET['trackId']);
		$sql = $db->prepare("
			INSERT INTO
				votes (trackId, ip, vote)
			VALUES
				(?,INET_ATON(?),?)
			ON DUPLICATE KEY UPDATE
				vote = ?");
		$sql->execute(array($trackId, $_SERVER['REMOTE_ADDR'], $vote, $vote));
	}
}