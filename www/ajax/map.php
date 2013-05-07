<?php
header("content-type: text/json");

$f = fsockopen("localhost", 8001, $errno, $errstr, 3);
$raw = "";
while ($f && !feof($f) && connection_status()===0) {
        $raw .= @fgets($f, 1024);
}
fclose($f);

echo $raw;