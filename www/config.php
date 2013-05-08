<?php

ini_set("display_errors", "1");
error_reporting(-1);

session_cache_expire(5*24*60*60);
session_start();

$db = new PDO("mysql:host=localhost;dbname=".$mysqlDatabase, $mysqlUser, $mysqlPassword);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING); // Display errors, but continue script

$state = isset($_SESSION['state'])?$_SESSION['state']:array("admin" => false);
$_SESSION['state'] = &$state;

require_once dirname(__DIR__)."/hidden.php";

$trackWait = 5*60; // In minutes
$queueWait = 20; // In minutes
$queueMaxSize = 10;
