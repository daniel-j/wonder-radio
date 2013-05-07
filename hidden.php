<?php

$mysqlUser = 'USERNAME';
$mysqlPassword = "PASSWORD";
$mysqlDatabase = 'DATABASE';
$mpdPassword = $mysqlPassword;
$adminPass = $mysqlPassword;


session_cache_expire(5*24*60*60);
session_start();

$db = new PDO("mysql:host=localhost;dbname=".$mysqlDatabase, $mysqlUser, $mysqlPassword);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING); // Display errors, but continue script

$state = isset($_SESSION['state'])?$_SESSION['state']:array("admin" => false);
$_SESSION['state'] = &$state;