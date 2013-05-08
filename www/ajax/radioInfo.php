<?php
require_once "../config.php";
header("Content-Type: text/plain;charset=utf-8");

$icecastInfo = json_decode(utf8_decode(file_get_contents($icecastpInfoUrl)), true)[$icecastMount];

$sqlTracks = $db->prepare("
	SELECT
		tracks.*,
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
$track = $track[0];

//$title = $icecastInfo['title'].($icecastInfo['artist'] !== " - ".$icecastInfo['artist']? : "");

echo json_encode(array(
	"title" => empty($track['title'])? $track['file'] : $track['title'],
	"artist" => $track['artist'],
	"listeners" => $icecastInfo['listeners'],
	"listeners_peak" => $icecastInfo['listeners_peak'],
	"rating" => $track['rating'],
	"vote" => $track['vote']
));
