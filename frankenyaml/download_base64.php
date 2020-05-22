<?php

if (!($raw = $_SERVER['QUERY_STRING']))
    die("nothing");


if (!($fn = $_SERVER['PATH_INFO']))
    die("nothing");

$fn = substr($fn, 1);

if(substr($fn,-4) != "yaml")
    die("filename $fn is not .yaml, we do not do this shit here");

if (!($text = base64_decode($raw)))
    die("text not base64");


header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fn) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($text));
echo $text;
exit;




?>
