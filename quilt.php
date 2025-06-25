<?php
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: text/plain');
$json = file_get_contents('quilts.json');
$data = json_decode($json);

foreach ($data as $post) {
   echo "+" . str_repeat("=", 50) . "+\n\n";
   echo "From: " . $post->author . " on " . date("F j, Y, g:i a", strtotime($post->date)) . "\n\n";
   echo wordwrap($post->body, 50, "\n");
   echo "\n\n+" . str_repeat("=", 50) . "+\n\n";
}
