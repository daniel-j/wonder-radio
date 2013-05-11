<?php
require_once dirname(__DIR__)."/config.php";

if (isset($_GET['id']) && isset($_GET['reject'])) {
	if (!$state['admin']) exit;

	$id = intval($_GET['id']);
	$reject = intval($_GET['reject']) === 1;
	
	$sql = $db->prepare("
		UPDATE
			suggestions
		SET
			rejected = ?".($reject?', accepted = 0':'')."
		WHERE
			id = ?");
	$sql->execute(array($reject, $id));

	exit;
}

if (isset($_GET['id']) && isset($_GET['accept'])) {
	if (!$state['admin']) exit;

	$id = intval($_GET['id']);
	
	$sql = $db->prepare("
		UPDATE
			suggestions
		SET
			accepted = 1,
			rejected = 0
		WHERE
			id = ?");
	$sql->execute(array($id));

	exit;
}

$response = array(
	"remaining" => 0,
	"time" => time()
);

$sql = $db->prepare("
	SELECT
		ip, UNIX_TIMESTAMP(timeSuggested) as time, DATE_SUB(NOW(), INTERVAL $suggestWait MINUTE) as sub
	FROM
		suggestions
	WHERE
		ip = INET_ATON(?) AND accepted = 0 AND (timeSuggested > DATE_SUB(NOW(), INTERVAL $suggestWait MINUTE))
	ORDER BY
		timeSuggested DESC
	LIMIT 1");
$sql->execute(array($_SERVER['REMOTE_ADDR']));
$lastSuggestion = $sql->fetch();
if ($lastSuggestion) {
	$remaining = ($suggestWait*60-(time()-$lastSuggestion['time']));
	
	$response['remaining'] = max($remaining, 0);
}

if (isset($_POST['username']) && !empty($_POST['username']) && isset($_POST['suggestion']) && !empty($_POST['suggestion']) && isset($_POST['reason']) && !empty($_POST['reason'])) {
	$response["tooFast"] = false;

	if ($lastSuggestion) {
		// user cant suggest another
		$response['tooFast'] = true;
	} else {
		$sql = $db->prepare("
			INSERT INTO
				suggestions (username, suggestion, reason, ip)
			VALUES
				(?,?,?,INET_ATON(?))");
		$sql->execute(array($_POST['username'], $_POST['suggestion'], $_POST['reason'], $_SERVER['REMOTE_ADDR']));
	}
	echo json_encode($response);
	exit;
}

$page = isset($_GET['page'])? intval($_GET['page']) : 1;
$sql = $db->prepare("
	SELECT
		COUNT(id) as totalSuggestions
	FROM
		suggestions
	".($state['admin']?"":"WHERE ip = INET_ATON(?) AND accepted = 0")."
	LIMIT 1");
$sql->execute(array($_SERVER['REMOTE_ADDR']));
$totalSuggestions = $sql->fetch()['totalSuggestions'];
$lastpage = ceil($totalSuggestions/$suggesionsPerPage);
$page = max(min($page, $lastpage), 1);

$offset = $searchResultsPerPage*($page-1);

$response['page'] = $page;
$response['lastPage'] = $lastpage;
$response['totalSuggestions'] = $totalSuggestions;

$response["list"] = array();

$sql = $db->prepare("
	SELECT
		id, username, suggestion, reason, rejected, accepted, UNIX_TIMESTAMP(timeSuggested) as time
	FROM
		suggestions
	".($state['admin']?"":"WHERE ip = INET_ATON(?)")."
	ORDER BY
		accepted ASC, rejected ASC, timeSuggested DESC
	LIMIT
		$offset, $suggesionsPerPage");
$sql->execute(array($_SERVER['REMOTE_ADDR']));
$list = $sql->fetchAll();

foreach ($list as $suggestion) {
	$response['list'][] = array(
		'id' => $suggestion['id'],
		'time' => $suggestion['time'],
		'rejected' => intval($suggestion['rejected']) !== 0,
		'accepted' => intval($suggestion['accepted']) !== 0,
		'username' => utf8_encode($suggestion['username']),
		'suggestion' => utf8_encode($suggestion['suggestion']),
		'reason' => utf8_encode($suggestion['reason'])
	);
}

echo json_encode($response);