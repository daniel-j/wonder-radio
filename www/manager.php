<?php
require_once "config.php";
header("Content-Type: text/html;charset=utf-8");

if (!$state['admin']) {
	echo "You are not logged in.";
	exit;
}



$songsPerPage = 10;

if (isset($_POST['id']) && isset($_POST['file']) && isset($_POST['title']) && isset($_POST['artist']) && isset($_POST['album'])) {

	$sql = $db->prepare("UPDATE tracks SET title = ?, artist = ?, album = ? WHERE id = ?");
	$sql->execute($_POST['title'], $_POST['artist'], $_POST['album'], $_POST['id']);

	exit;
}

// include getID3() library (can be in a different directory if full path is specified)
require_once('../getid3/getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;

$sql = $db->prepare("SELECT count(id) as totalTracks FROM tracks");
$sql->execute();
$totalTracks = $sql->fetch()['totalTracks'];

$page = isset($_GET['page']) ? intval($_GET['page'])-1 : 0;
$offset = $songsPerPage*$page;

$lastpage = ceil($totalTracks/$songsPerPage);

$pagination = "<div class='pagination'>";
if ($page > 0) {
	$pagination .= "<a href=\"?page=".($page)."\">«</a> ";
}
$pagination .= $page+1;
if ($page < $lastpage) {
	$pagination .= " <a href=\"?page=".($page+2)."\">»</a>";
}
$pagination .= "</div>";


$sql = $db->prepare("SELECT id, file, title, artist, album FROM tracks ORDER BY file LIMIT $offset, $songsPerPage");
$sql->execute();
$tracks = $sql->fetchAll();

echo $pagination;

echo "<table border=0>";
echo <<<EQD
<style>
	
	body {
		font-family: Verdana;
	}

	table {
		font-size: 11px;
		width: 100%;
	}
	table td {
		vertical-align: top;
	}

	input[type=text] {
		padding: 3px;
		width: 100%;
		margin-bottom: 10px;
		font-size: 13px;
	}

	.pagination {
		font-size: 20px;
		font-weight: bold;
	}
</style>
EQD;
echo "<thead><tr><th>Id</th><th>Filename</th><th width=1>Title</th><th width=1>Artist</th><th width=1>Album</th><th></th></tr></thead>";
foreach($tracks as $track) {
	$fileinfo = $getID3->analyze($musicPath.$track['file']);
	$id3 = $fileinfo['tags']['id3v2'];
	echo "<form method=\"post\"><input type=hidden name='id' value='{$track['id']}'><input type=hidden name='file' value='{$track['file']}'><tr><td rowspan=2>{$track['id']}</td><td rowspan=2>{$track['file']}</td><td>".$id3['title'][0]."</td><td>".$id3['artist'][0]."</td><td>".$id3['album'][0]."</td><td></td></tr>";
	echo "<tr><td><input type=text name=title value=\"".htmlentities($track['title'])."\"></td><td><input type=text name=artist value=\"".htmlentities($track['artist'])."\"></td><td><input type=text name=album value=\"".htmlentities($track['album'])."\"></td><td><input type=\"submit\"</td></tr></form>";
}
echo "</table>";

echo $pagination;