<?php require_once "config.php"; ?>
<!doctype html>
<html class="popup">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=360">
	<title><?php echo $radioTitle; ?></title>
	<link rel="stylesheet" href="style/style.php">
</head>
<body>

<header>
	<h1><?php echo $radioTitle; ?></h1>
</header>

<div id="wrapper">

	<?php if (isset($_GET['flash'])) { ?>
		<div style="text-align: center;" class="pinkbox">

			<!-- BEGINS: AUTO-GENERATED MUSES RADIO PLAYER CODE -->
			<script type="text/javascript" src="http://hosted.musesradioplayer.com/mrp.js"></script>
			<script type="text/javascript">
			MRP.insert({
			'url':"<?php echo $streamUrl; ?>",
			'codec':'mp3',
			'volume':70,
			'autoplay':true,
			'buffering':5,
			'title':"<?php echo $radioTitle; ?>",
			'wmode':'transparent',
			'skin':'darkconsole',
			'skin':'mcclean',
			'width':180,
			'height':60
			});
			</script>
			<!-- ENDS: AUTO-GENERATED MUSES RADIO PLAYER CODE -->

			<div id="currentSongWrapper">
				Current listeners: <strong><span id="currentListeners"></span></strong>
				<div id="currentSong"></div>
				<div id="currentSongVote"></div>
			</div>
		</div>
		

	<?php } else { ?>

		<div class="pinkbox">
			<div id="radioPlayer">
				<button type="button" id="playstopbtn" disabled></button>
				<input type="range" id="radioVolume" min="0" max="1" step="0.01" value="0.7">
			</div>
			<div id="currentSongWrapper">
				Current listeners: <strong><span id="currentListeners"></span></strong>
				<div id="currentSong"></div>
				<div id="currentSongVote"></div>
			</div>
		</div>

		<footer>
			If you can't hear anything, try open this URL in a media player, for example in VLC:<br>
			<a href="<?php echo $streamUrl; ?>"><?php echo $streamUrl; ?></a><br>
			You can also try the flash-based player <a href="?flash">here</a>.
		</footer>

		<script>
		var stationUrl = "<?php echo $streamUrl; ?>";
		var autoplay = true;
		</script>
		<script src="js/simplePlayer.js"></script>

	<?php } ?>

</div>

<script>
var icecastpInfoUrl = "<?php echo $icecastpInfoUrl; ?>";
var icecastMount = "<?php echo $icecastMount; ?>";
</script>
<script src="js/updateCurrent.js"></script>
</body>
</html>