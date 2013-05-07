<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>DJazz Radio</title>
	<style>
	body {
		color: #555;
		text-align: center;
	}
	</style>
</head>
<body>

<h2>DJazz's Music Radio</h2>

<?php if (isset($_GET['flash'])) { ?>

<!-- BEGINS: AUTO-GENERATED MUSES RADIO PLAYER CODE -->
<script type="text/javascript" src="http://hosted.musesradioplayer.com/mrp.js"></script>
<script type="text/javascript">
MRP.insert({
	'url':'http://djazz.mine.nu:1338/stream',
	'lang':'en',
	'codec':'mp3',
	'volume':100,
	'autoplay':true,
	'buffering':5,
	'title':'Pony Music Radio',
	'skin':'darkconsole',
	'width':190,
	'height':62
});
</script>
<!-- ENDS: AUTO-GENERATED MUSES RADIO PLAYER CODE -->
<br><br>
<marquee id="currentSong"></marquee>
Current listeners: <strong><span id="currentListeners"></span></strong>

<?php } else { ?>

<button type="button" id="playstopbtn" disabled></button>

<br><br>
<marquee id="currentSong"></marquee>
Current listeners: <strong><span id="currentListeners"></span></strong>

<hr>
If you can't hear anything, try open this URL in a media player, for example in VLC.:<br>
MP3 stream: <a href="http://djazz.mine.nu:1338/stream">http://djazz.mine.nu:1338/stream</a><br>
<!--MP3 stream: <a href="http://djazz.mine.nu:1338/streamMP3">http://djazz.mine.nu:1338/streamMP3</a><br>-->
You can also try the flash-based player <a href="?flash">here</a>.

<script>
var stationUrl = "http://djazz.mine.nu:1338/stream";
var autoplay = true;
</script>
<script src="js/simplePlayer.js"></script>

<?php } ?>


<script src="js/updateCurrent.js"></script>
</body>
</html>