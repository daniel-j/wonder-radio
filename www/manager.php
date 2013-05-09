<?php
require_once "config.php";

if (!$state['admin']) {
	echo "You are not logged in.";
	exit;
}

// include getID3() library (can be in a different directory if full path is specified)
require_once('../getid3/getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

$sql = $db->prepare("SELECT id, file, title, artist, album FROM tracks ORDER BY file LIMIT 20");
$sql->execute();
$tracks = $sql->fetchAll();

echo "<table border>";
echo "<head><tr><th>Filename</th><th>Title</th><th>Artist</th><th>Album</th></tr></thead>";
foreach($tracks as $track) {
	$fileinfo = $getID3->analyze($musicPath.$track['file']);
	print_r($fileinfo['comments_html']);
	echo "<tr><td rowspan=2>{$track['file']}</td><td>{$track['title']}</td><td>{$track['artist']}</td><td>{$track['album']}</td></tr>";
	echo "<tr><td>{$track['title']}</td><td>{$track['artist']}</td><td>{$track['album']}</td></tr>";

}
echo "</table>";