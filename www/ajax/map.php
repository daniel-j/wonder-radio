<?php
header("content-type: text/json");

$f = @fsockopen("127.0.0.1", 8001, $errno, $errstr, 3);
if ($f) {
	$raw = "";
	while ($f && !feof($f) && connection_status()===0) {
	        $raw .= @fgets($f, 1024);
	}
	fclose($f);


	echo $raw;
} else {
	echo "[]";
}