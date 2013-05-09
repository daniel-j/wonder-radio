<?php

ini_set("display_errors", "1");
error_reporting(-1);

$icecastHost = "djazz.mine.nu";
$icecastPort = 1338;
$icecastMount = "/stream";
$radioTitle = "DJazz's Music Radio";

$trackWait = 2*60; // In minutes
$queueWait = 20; // In minutes
$queueMaxSize = 10;

// Make a hidden.php in the parent folder that looks like this:
/*
	<?php
	$mysqlUser = 'USERNAME';
	$mysqlPassword = 'PASSWORD';
	$mysqlDatabase = 'DATABASE';
	$mpdPassword = $mysqlPassword;
	$adminPass = $mysqlPassword;
*/

$streamUrl = "http://".$icecastHost.":".$icecastPort.$icecastMount;
$icecastpInfoUrl = "http://".$icecastHost.":".$icecastPort."/info_json.xsl";

session_cache_expire(5*24*60*60);
session_start();

require_once dirname(__DIR__)."/hidden.php";

$db = new PDO("mysql:host=localhost;dbname=".$mysqlDatabase, $mysqlUser, $mysqlPassword);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING); // Display errors, but continue script

$state = isset($_SESSION['state'])?$_SESSION['state']:array("admin" => false);
$_SESSION['state'] = &$state;


