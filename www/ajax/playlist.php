<?php
require_once dirname(__DIR__)."/config.php";
header("Content-Type: text/plain;charset=utf-8");

$sqlQueue = $db->prepare("
	SELECT
		tracks.*,
		UNIX_TIMESTAMP(queue.added) AS timeQueued,
		TIME_FORMAT(queue.added, '%H:%i') AS timeAdded,
		IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating
	FROM
		queue
	LEFT JOIN tracks ON
		tracks.id=queue.trackId
	ORDER BY
		queue.added DESC");
$sqlQueue->execute();
$queue = $sqlQueue->fetchAll();

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
		20");
$sqlTracks->execute(array($_SERVER['REMOTE_ADDR']));
$tracks = $sqlTracks->fetchAll();

$json = array();
$json['time'] = time();
$json['queue'] = [];
$json['history'] = [];

//<table class="playlist"><tr><th width=1></th><th>Title</th><th>Artist</th><th width=1>Plays</th><th width=1>Requests</th><th width=1>Rating</th><th width=1></th><th width=1></th></tr>

foreach ($queue as $track) {
	$class = "";
	
	$title = empty($track['title'])? $track['file'] : $track['title'];

	$rating = intval($track['rating']);

	$json['queue'][] = array(
		"id" => intval($track['id']),
		"title" => $title,
		"artist" => $track['artist'],
		"timeAdded" => intval($track['timeQueued']),
		"playCount" => intval($track['plays']),
		"requestCount" => intval($track['requests']),
		"rating" => intval($track['rating'])
	);

	//echo "	<tr class=\"queued ".(strlen($class) > 0? $class:'')."\"><td>{$track['timeAdded']}</td><td>{$title}</td><td>{$track['artist']}</td><td align=right>{$track['plays']}</td><td align=right>{$track['requests']}</td><td align=right>{$rating}</td><td colspan=2></td></tr>\n";
	
}
//echo "\n";
$isFirst = true;
foreach ($tracks as $track) {
	$class = "";
	if ($isFirst) {
		$class = "current";
		$track['timePlayed'] = '';
	}

	$voteUp = '';
	$voteDown = '';

	if ($track['vote'] == 1) {
		$voteUp = ' voted';
		$voteDown = ' notvoted';
	} else if ($track['vote'] == -1) {
		$voteUp = ' notvoted';
		$voteDown = ' voted';
	}
	$voteButtons = <<<EQD
	<form method="post"><input type="hidden" name="vote" value="1"><input type="hidden" name="track" value="{$track['id']}"><button class="vote up{$voteUp}">&#x25B2;</button></form></td><td>
	<form method="post"><input type="hidden" name="vote" value="-1"><input type="hidden" name="track" value="{$track['id']}"><button class="vote down{$voteDown}" data-track="{$track['id']}">&#x25BC;</button></form>
EQD;

	$title = empty($track['title'])? $track['file'] : $track['title'];
	$rating = intval($track['rating']);

	//echo "	<tr".(strlen($class) > 0? " class=\"{$class}\"":"")."><td>{$track['timePlayed']}</td><td>{$title}</td><td>{$track['artist']}</td><td align=right>{$track['plays']}</td><td align=right>{$track['requests']}</td><td align=right>{$rating}</td><td>{$voteButtons}</td></tr>\n";
	$isFirst = false;

	$json['history'][] = array(
		"id" => intval($track['id']),
		"title" => $title,
		"artist" => $track['artist'],
		"timePlayed" => intval($track['timePlayed']),
		"playCount" => intval($track['plays']),
		"requestCount" => intval($track['requests']),
		"rating" => intval($track['rating']),
		"vote" => intval($track['vote'])
	);
}

echo json_encode($json);

