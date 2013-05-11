<?php
require_once dirname(__DIR__)."/config.php";
require_once dirname(__DIR__)."/funcs.php";

if (!isset($_GET['q'])) {
	exit;
}
$remaining = getQueueRemainingTime();

$search = $_GET['q'];

$searchResult = array(
	"time" => time(),
	"remaining" => $remaining,
	"trackWait" => $trackWait
);

if ($search !== "") {

	$sql = $db->prepare("SELECT COUNT(*) FROM queue");
	$sql->execute();
	$qcount = $sql->fetchAll()[0][0];

	$searchResult["queueFull"] = $qcount >= $queueMaxSize;

	$page = isset($_GET['page'])? intval($_GET['page']) : 1;
	$sql = $db->prepare("
		SELECT
			COUNT(tracks.id) as matchCount
		FROM
			tracks
		WHERE
			MATCH(tags) AGAINST(?) OR tags LIKE ?
		LIMIT 1");
	$sql->execute(array($search, "%".$search."%"));
	$matchCount = $sql->fetch()['matchCount'];
	$lastpage = ceil($matchCount/$searchResultsPerPage);
	$page = max(min($page, $lastpage), 1);

	$offset = $searchResultsPerPage*($page-1);

	$searchResult["page"] = $page;
	$searchResult["lastPage"] = $lastpage;
	$searchResult["totalResults"] = $matchCount;

	$searchResult["result"] = array();

	$sql = $db->prepare("
		SELECT
			tracks.*,
			queue.id as qid,
			UNIX_TIMESTAMP(lastplayed) AS timePlayed,
			MATCH(tags) AGAINST(?) AS relevance,
			TIMESTAMPDIFF(MINUTE,lastplayed,NOW()) as lastplayedTime,
			IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating
		FROM
			tracks
		LEFT JOIN queue ON
			tracks.id=queue.trackId
		WHERE
			MATCH(tags) AGAINST(?) OR tags LIKE ?
		ORDER BY
			added DESC, relevance DESC
		LIMIT
			$offset, $searchResultsPerPage");

	$sql->execute(array($search, $search, "%".$search."%"));

	$result = $sql->fetchAll();

	//$searchResult .= "<table class=\"search-result\"><tr><th width=1></th><th>Title</th><th>Artist</th><th width=1>Plays</th><th width=1>Requests</th><th width=1>Rating</th><th width=1></th></tr>";
	foreach ($result as $track) {
		$title = empty($track['title'])? $track['file'] : $track['title'];
		
		/*if ($track['qid'] !== NULL) {
			$requestButton = "Queued";
		} else if ($track['lastplayedTime'] != NULL && $track['lastplayedTime'] < $trackWait) {
			
			$requestButton = "<strong>".($trackWait-$track['lastplayedTime'])."</strong>&nbsp;min&nbsp;left";
		} else if ($qcount >= $queueMaxSize) {
			$requestButton = "Queue&nbsp;full";
		} else if ($remaining > 0) {
			$requestButton = "-";
		} else {
			$requestButton = "<form method=\"post\" action=\"./ajax/request.php\"><input type=\"hidden\" name=\"request\" value=\"{$track['id']}\"><button>Request</button></form>";
		}*/

		$searchResult['result'][] = array(
			"id" => intval($track['id']),
			"title" => utf8_encode($title),
			"artist" => utf8_encode($track['artist']),
			"timePlayed" => intval($track['timePlayed']),
			"playCount" => intval($track['plays']),
			"requestCount" => intval($track['requests']),
			"rating" => intval($track['rating']),
			"isQueued" => $track['qid'] !== NULL
		);
		
		//$searchResult .= "	<tr><td>{$track['timePlayed']}</td><td>{$title}</td><td>{$track['artist']}</td><td align=right>{$track['plays']}</td><td align=right>{$track['requests']}</td><td align=right>{$rating}</td><td>".$requestButton."</td></tr>\n";
	}
	//$searchResult .= "</table>";
}

echo json_encode($searchResult);
