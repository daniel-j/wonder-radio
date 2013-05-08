<?php
require_once "config.php";

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

$remaining = getQueueRemainingTime();
if (isset($_POST['request'])) {
	$req = intval($_POST['request']);
	$sql = $db->prepare("SELECT COUNT(*) FROM queue");
	$sql->execute();
	$rows = $sql->fetchAll();
	$qcount = $rows[0][0];
	
	if ($qcount < $queueMaxSize) {

		$remaining = getQueueRemainingTime();
		
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
			$sql->execute(array($req));
			if($sql->rowCount() > 0) {
				$sql = $db->prepare("INSERT INTO queue (trackId, ip) VALUES (?,INET_ATON(?))");
				$sql->execute(array($req, $_SERVER['REMOTE_ADDR']));

				$sql = $db->prepare("UPDATE tracks SET requests = requests + 1 WHERE id = ?");
				$sql->execute(array($req));

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
			print_r($result);
		}
	} else {
		echo "Queue is full";
	}
	exit;
}

$searchResult = "";
if (isset($_GET['search'])) {
	$search = $_GET['search'];
	$sql = $db->prepare("SELECT COUNT(*) FROM queue");
	$sql->execute();
	$qcount = $sql->fetchAll()[0][0];

	

	$sql = $db->prepare("
		SELECT
			tracks.*,
			queue.id as qid,
			CASE
				WHEN lastplayed IS NOT NULL THEN
					TIME_FORMAT(lastplayed, '%H:%i')
				ELSE
					''
			END as timePlayed,
			MATCH(tags) AGAINST(?) as relevance,
			TIMESTAMPDIFF(MINUTE,lastplayed,NOW()) as lastplayedTime,
			IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating
		FROM
			tracks
		LEFT JOIN queue ON
			tracks.id=queue.trackId
		WHERE
			MATCH(tags) AGAINST(?) OR tags LIKE ?
		ORDER BY
			lastplayedTime*rating DESC
		LIMIT 50");
	
	$sql->execute(array($search, $search, "%".$search."%"));
	
	$result = $sql->fetchAll();

	$searchResult .= "<table class=\"search-result\"><tr><th width=1></th><th>Title</th><th>Artist</th><th width=1>Plays</th><th width=1>Requests</th><th width=1>Rating</th><th width=1></th></tr>";
	foreach ($result as $track) {
		$title = empty($track['title'])? $track['file'] : $track['title'];
		$requestButton = "";
		if ($track['qid'] !== NULL) {
			$requestButton = "Queued";
		} else if ($track['lastplayedTime'] != NULL && $track['lastplayedTime'] < $trackWait) {
			
			$requestButton = "<strong>".($trackWait-$track['lastplayedTime'])."</strong>&nbsp;min&nbsp;left";
		} else if ($qcount >= $queueMaxSize) {
			$requestButton = "Queue&nbsp;full";
		} else if ($remaining > 0) {
			$requestButton = "-";
		} else {
			$requestButton = "<form method=\"post\" action=\"./\"><input type=\"hidden\" name=\"request\" value=\"{$track['id']}\"><button>Request</button></form>";
		}

		$rating = intval($track['rating']);
		
		$searchResult .= "	<tr><td>{$track['timePlayed']}</td><td>{$title}</td><td>{$track['artist']}</td><td align=right>{$track['plays']}</td><td align=right>{$track['requests']}</td><td align=right>{$rating}</td><td>".$requestButton."</td></tr>\n";
	}
	$searchResult .= "</table>";
}


?><!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
	<title>DJazz Radio</title>
	<link rel="stylesheet" href="style/style.php" type="text/css">
	<script type="text/javascript">
	
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-5181445-3']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
	
	</script>
</head>
<body>

<div id="wrapper">
	<header id="topheader">
		<img id="pinkieheadphones" src="img/pinkie-headphones.png" height=120>
		<h1><a href="./">DJazz's Music Radio</a></h1>
		<h2>Hosted on a Raspberry Pi.</h2>
	</header>

	<div id="contentWrapper">

		<div id="rightcol">
			<div id="sidepanel">
				<h2>Tune in</h2>
				<marquee id="currentSong"></marquee>
				<br>
				<div id="panelbuttons">
					<button onclick="window.open('visuals/', 'radio-player-visuals', 'width=720,height=480');">Player with visualizations</button>
					<button onclick="window.open('popup.php', 'radio-player', 'width=340,height=300');">Classic popup player</button>
					<button id="playstopbtn" disabled>Play</button>
				</div>
				<div id="currentListenersWrapper">Current listeners: <strong><span id="currentListeners"></span></strong></div>

				<?php
				if ($state['admin']) {
					?>
					<hr>
					<a href="admin.php?next">&gt;&gt; next song &gt;&gt;</a><br>
					<br>
					<a href="update.php" onclick="return confirm('Are you sure you want to update database?');">Update database</a><br>
					<br>
					<a href="admin.php?logout">Logout</a>
					<?php
				}
				?>
			</div>
		</div>

		<div id="playlistContainer">
			<table class="playlist">
				<thead>
					<tr><th width=1></th><th>Title</th><th>Artist</th><th width=1>Plays</th><th width=1>Requests</th><th width=1>Rating</th><th width=1></th><th width=1></th></tr>
				</thead>
				<tbody id="playlistBody"></tbody>
			</table>
		</div>

	</div>

	<div id="searchContainer">
		
		<a name="search"><h2>Search &amp; request</h2></a>
		<form method="get" action="#search">
			<input type="search" name="search"<?php if(isset($_GET['search'])) echo " value=\"".$_GET['search']."\" autofocus"; ?>>
			<input type="submit" value="Search">
		</form>
		<?php

		
		if ($remaining !== 0) {
			echo "<br>You must wait <strong>".$remaining."</strong> more minute(s) before you can queue another track, or until the last track that you enqueued has been played.<br><br>";
		}

		echo $searchResult;

		?>
	</div>
	<footer>
		<img id="basscannon" src="img/bass-cannon.png" width=200>
		<canvas width=454 height=244 id="geocanvas"></canvas>
		<div id="footercontent">
			Created by <a href="http://djazz.mine.nu/">djazz</a><br>
			Powered by <a href="http://www.musicpd.org/">MPD</a>, Icecast2, PHP, MySQL, nginx, ponies, Node.JS and LESS.<br>
			It's all running on a <a href="http://www.raspberrypi.org/">Raspberry Pi</a> with Arch Linux ARM.<br>
			<br>
			View sourcecode: <a href="http://djazz.mine.nu:1337/source.php">source.php</a><br>
			Sourcecode on github: <a href="https://github.com/daniel-j/wonder-radio">https://github.com/daniel-j/wonder-radio</a><br>
			<!--Download sourcecode here (mostly outdated): <a href="wonder-radio.zip">wonder-radio.zip</a><br>-->
			<br>
			Other radio stations:
			<a href="http://everfree.net/channels/everfree-radio/playlist/">Everfree Radio</a>
			<a href="http://ponify.me/">Celestia Radio</a>
			<a href="https://www.team9000.net/radio/cloudsdale-radio.1">Cloudsdale Radio</a>
			<br>
			<br>
			<?php
			if (!$state['admin']) {
				?><a href="admin.php">Admin login</a><?php
			}
			?>
		</div>
	</footer>
</div>

<script>
var stationUrl = "http://djazz.mine.nu:1338/stream";
var autoplay = false;
</script>
<script src="js/simplePlayer.js"></script>
<script src="js/updateCurrent.js"></script>
<script src="js/updatePlaylist.js"></script>
</body>
</html>
