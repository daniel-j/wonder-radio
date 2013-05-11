<?php
require_once "config.php";
require_once "funcs.php";

$remaining = getQueueRemainingTime();

?><!doctype html>
<html class="main">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
	<title><?php echo $radioTitle; ?></title>
	<link rel="stylesheet" href="style/style.php" type="text/css">
	<link rel="icon" type="image/png" href="img/logo.png">
</head>
<body>

<div id="wrapper">
	<header id="topheader">
		<img id="pinkieheadphones" src="img/pinkie-headphones.png" height=120>
		<h1><a href="./"><?php echo $radioTitle; ?></a></h1>
		<h2>Hosted on a Raspberry Pi. 100% MLP-related music.</h2>
	</header>

	<div id="contentWrapper">

		<div id="rightcol">
			<div id="sidepanel">
				<h2>Tune in</h2>
				<div id="panelbuttons">
					<button onclick="window.open('visuals/?url=<?php echo $streamUrl; ?>', 'radio-player-visuals', 'width=720,height=480');">Player with visualizations</button>
					<button onclick="window.open('popup.php', 'radio-player', 'width=360,height=310');">Classic popup player</button>
					<button id="playstopbtn" disabled>Play</button>
				</div>
				<div id="currentSongWrapper">
					Current listeners: <strong><span id="currentListeners"></span></strong>
					<div id="currentSong"></div>
					<div id="currentSongVote"></div>
				</div>

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
					<tr><th width=1></th><th>Title</th><th>Artist</th><th width=1>Plays</th><th width=1>Requests</th><th width=1 colspan=3>Rating</th></tr>
				</thead>
				<tbody id="playlistBody"></tbody>
			</table>
		</div>

	</div>

	<div id="searchContainer">
		<h2 id="toggleSearch"><a name="search">Search &amp; request</a></h2>
		<form id="searchForm">
			<input type="text" id="searchInput" name="search"<?php if(isset($_GET['search'])) echo " value=\"".htmlentities($_GET['search'])."\" autofocus"; ?>>
			<input type="submit" value="Search">

			<div id="queueWait"></div>
		</form>

		<table id="searchResult" class="search-result">
			<thead>
				<tr><th width=1></th><th>Title</th><th>Artist</th><th width=1>Plays</th><th width=1>Requests</th><th width=1>Rating</th><th width=1></th></tr>
			</thead>
			<tbody id="searchBody"></tbody>
			<tfoot><td colspan="7" id="searchPagination" class="pagination"></td></tfoot>
		</table>

	</div>

	<div id="suggestContainer"<?php echo $state['admin']?' class="show"':''; ?>>
		<h2 id="toggleSuggestions"><a name="suggest">Suggest new music</a></h2>
		<form id="suggestForm" method="post" action="ajax/suggest.php">
			<table>
				<tr><th>Your name*</th><th>YouTube-link/song name*</th><th>Reason/motivation*</th><th></th></tr>
				<tr><td><input type="text" name="username" required size=15></td><td><input type="text" name="suggestion" required size=30></td><td><input type="text" name="reason" required size=25></td><td><input type="submit" value="Suggest"></td></tr>
			</table>
		</form>
		<div id="suggestWait"></div>
		<table id="suggestTable">
			<thead>
				<tr><th width=1></th><th width=15%>Username</th><th>Suggestion</th><th>Reason/motivation</th><th width=1></th><th width=1></th></tr>
			</thead>
			<tbody id="suggestBody"></tbody>
			<tfoot><td colspan="6" id="suggestPagination" class="pagination"></td></tfoot>
		</table>
	</div>

	<footer>
		<img id="basscannon" src="img/bass-cannon.png" width=200>
		<canvas width=454 height=244 id="geocanvas"></canvas>
		<div id="footercontent">
			<?=getTotalTrackCount()?> tracks in database.<br>
			Created by <a href="http://djazz.mine.nu/">djazz</a><br>
			Powered by <a href="http://www.musicpd.org/">MPD</a>, Icecast2, PHP, MySQL, nginx, ponies, Node.JS and LESS.<br>
			It's all running on a <a href="http://www.raspberrypi.org/">Raspberry Pi</a> with Arch Linux ARM.<br>
			<br>
			View sourcecode: <a href="source.php">source.php</a><br>
			Sourcecode on github: <a href="https://github.com/daniel-j/wonder-radio">https://github.com/daniel-j/wonder-radio</a><br>
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
var stationUrl = "<?php echo $streamUrl; ?>";
var autoplay = false;

var icecastpInfoUrl = "<?php echo $icecastpInfoUrl; ?>";
var icecastMount = "<?php echo $icecastMount; ?>";

var isAdmin = <?php echo $state['admin']? 'true':'false'; ?>;
</script>
<script src="js/simplePlayer.js"></script>
<script src="js/updateCurrent.js"></script>
<script src="js/updateMain.js"></script>
</body>
</html>
