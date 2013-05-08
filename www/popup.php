<?php require_once "config.php"; ?>
<!doctype html>
<html class="popup">
<head>
	<meta charset="utf-8">
	<title><?php echo $radioTitle; ?></title>
	<link rel="stylesheet" href="style/style.php">
	<style>
	body {
		color: #555;
		text-align: center;
	}
	</style>
</head>
<body>

<h2><?php echo $radioTitle; ?></h2>

<?php if (isset($_GET['flash'])) { ?>

<!-- BEGINS: AUTO-GENERATED MUSES RADIO PLAYER CODE -->
<script type="text/javascript" src="http://hosted.musesradioplayer.com/mrp.js"></script>
<script type="text/javascript">
MRP.insert({
	'url':"<?php echo $streamUrl; ?>",
	'lang':'en',
	'codec':'mp3',
	'volume':100,
	'autoplay':true,
	'buffering':5,
	'title':"<?php echo $radioTitle; ?>",
	'skin':'darkconsole',
	'width':190,
	'height':62
});
</script>
<!-- ENDS: AUTO-GENERATED MUSES RADIO PLAYER CODE -->
<br><br>
<div id="currentSongWrapper">
	Current listeners: <strong><span id="currentListeners"></span></strong>
	<div id="currentSong"></div>
	<div id="currentSongVote"></div>
</div>

<?php } else { ?>

<button type="button" id="playstopbtn" disabled></button>
<br><br>
<div id="currentSongWrapper">
	Current listeners: <strong><span id="currentListeners"></span></strong>
	<div id="currentSong"></div>
	<div id="currentSongVote"></div>
</div>

<hr>
If you can't hear anything, try open this URL in a media player, for example in VLC.:<br>
<a href="<?php echo $streamUrl; ?>"><?php echo $streamUrl; ?></a><br>
You can also try the flash-based player <a href="?flash">here</a>.

<script>
var stationUrl = "<?php echo $streamUrl; ?>";
var autoplay = true;
</script>
<script src="js/simplePlayer.js"></script>

<?php } ?>

<script>
var icecastpInfoUrl = "<?php echo $icecastpInfoUrl; ?>";
var icecastMount = "<?php echo $icecastMount; ?>";
</script>
<script src="js/updateCurrent.js"></script>
</body>
</html>