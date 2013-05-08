<?php
require_once "../config.php";

$mounts = json_decode(file_get_contents($icecastpInfoUrl), true);
unset($mounts[""]);

$sqlTracks = $db->prepare("
	SELECT
		tracks.*,
		UNIX_TIMESTAMP(lastplayed) AS timePlayed,
		(SELECT vote FROM votes WHERE tracks.id=votes.trackId AND votes.ip=INET_ATON(?)) as vote,
		IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating
	FROM
		tracks
	WHERE
		lastplayed IS NOT NULL
	ORDER BY
		lastplayed DESC
	LIMIT
		1");
$sqlTracks->execute(array($_SERVER['REMOTE_ADDR']));
$tracks = $sqlTracks->fetchAll();


print_r($mounts);
print_r($tracks[0]);