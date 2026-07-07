<?php
$url = 'http://196.220.119.61:8080/testfinancials.php?id=N02423145F';
$body = file_get_contents($url);
echo "LENGTH=" . strlen($body) . "\n";
echo "HEAD=" . substr($body, 0, 500) . "\n";
echo "POS=" . strpos($body, '{') . "\n";
$json = substr($body, strpos($body, '{'));
$decoded = json_decode($json, true);
echo "JSON_ERROR=" . json_last_error() . "\n";
echo "JSON_ERROR_MSG=" . json_last_error_msg() . "\n";
echo "DECODE_IS_ARRAY=" . (is_array($decoded) ? 'yes' : 'no') . "\n";
echo "JSON_HEAD=" . substr($json, 0, 200) . "\n";
