<?php
require_once "config.php";
header("Content-Type: text/html;charset=utf-8");

if (!$state['admin']) {
	echo "You are not logged in.";
	exit;
}

// include getID3() library (can be in a different directory if full path is specified)
require_once('../getid3/getid3/getid3.php');

// Initialize getID3 engine
$getID3 = new getID3;


$songsPerPage = 10;

if (isset($_POST['id']) && isset($_POST['file']) && isset($_POST['title']) && isset($_POST['artist']) && isset($_POST['album'])) {

	//$sql = $db->prepare("UPDATE tracks SET title = ?, artist = ?, album = ? WHERE id = ?");
	//$sql->execute($_POST['title'], $_POST['artist'], $_POST['album'], $_POST['id']);

	/*require_once('../getid3/getid3/write.php');

	$tagwriter = new getid3_writetags;
	$tagwriter->filename = $musicPath.$_POST['file'];
	$tagwriter->tagformats = array('id3v2.3');
	$tagwriter->overwrite_tags = true;
	$tagwriter->tag_encoding = "UTF-8";
	$tagwriter->remove_other_tags = false;

	$tagwriter->tag_data = array(
		'title' => array($_POST['title']),
		'artist' => array($_POST['artist']),
		'album' => array($_POST['album'])
	);

	if ($tagwriter->WriteTags()) {
		echo 'Successfully wrote tags<br>';
		if (!empty($tagwriter->warnings)) {
			echo 'There were some warnings:<br>'.implode('<br><br>', $tagwriter->warnings);
		}
	} else {
		echo 'Failed to write tags!<br>'.implode('<br><br>', $tagwriter->errors);
	}*/

	echo "This feature has been disabled";

	exit;
}

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
	$id3Title = !empty($id3['title']) ? $id3['title'][0] : "";
	$id3Artist = !empty($id3['artist']) ? $id3['artist'][0] : "";
	$id3Album = !empty($id3['album']) ? $id3['album'][0] : "";
	
	echo "<form method=\"post\"><input type=hidden name='id' value='{$track['id']}'><input type=hidden name='file' value='{$track['file']}'><tr><td rowspan=2>{$track['id']}</td><td rowspan=2>{$track['file']}</td><td>".$id3Title."</td><td>".$id3Artist."</td><td>".$id3Album."</td><td></td></tr>";
	echo "<tr><td><input type=text name=title value=\"".htmlentities($id3Title)."\"></td><td><input type=text name=artist value=\"".htmlentities($id3Artist)."\"></td><td><input type=text name=album value=\"".htmlentities($id3Album)."\"></td><td><input type=\"submit\"</td></tr></form>";
}
echo "</table>";

echo $pagination;
