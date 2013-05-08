<?php
require_once "../config.php";

$json = file_get_contents($icecastpInfoUrl);
echo $json;