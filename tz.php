<?php
error_reporting(E_ERROR | E_PARSE);
header("Content-Type: text/plain");

if (!isset($_GET['userid'])) {
    http_response_code(400);
    echo "Missing userid parameter";
    exit;
}

$userId = escapeshellarg($_GET['userid']);
$slackToken = 'xoxb-XXXXXXXXXXXXXXXXXXXXXXXXXX';

$cmd = "curl -s -H 'Authorization: Bearer $slackToken' 'https://slack.com/api/users.info?user=$userId'";
$response = shell_exec($cmd);

if (!$response) {
    http_response_code(502);
    echo "Etc/GMT";
    exit;
}

$data = json_decode($response, true);

if (!isset($data['ok']) || !$data['ok']) {
    http_response_code(400);
    echo "Etc/GMT";
    exit;
}

$tz = $data['user']['tz'] ?? null;

if ($tz) {
    echo $tz;
} else {
    echo "Etc/GMT";
}
