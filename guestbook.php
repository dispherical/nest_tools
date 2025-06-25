<?php
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: text/plain');
$source_ip = $_SERVER['REMOTE_ADDR'];
$source_port = $_SERVER['REMOTE_PORT'];
$dest_port = $_SERVER['SERVER_PORT'];

function queryIdent($source_ip, $source_port, $dest_port) {
    $sock = fsockopen("127.0.0.1", 113, $errno, $errstr, 2);
    if (!$sock) {
        return "Error connecting to identd: $errstr ($errno)";
    }

    fwrite($sock, "$source_port, $dest_port\r\n");
    $response = fgets($sock);
    fclose($sock);

    $parts = explode(':', $response);

    if (count($parts) < 2) {
        return "unknown";
    }

    $username = trim(end($parts));

    return $username ?: "unknown";
}

function get_quilt_columns() {
    if (isset($_GET['cols']) && is_numeric($_GET['cols'])) {
        return max(1, min(6, intval($_GET['cols'])));
    }

    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/mobile|android|iphone|ipad|tablet/i', $ua)) {
        return 1;
    } elseif (preg_match('/macintosh|windows|linux/i', $ua)) {
        return 4;
    }

    return 2;
}


function quilt_block($post) {
    $wrapped = explode("\n", wordwrap($post->body, 24));
    $lines = [];
    $lines[] = ".--------------------------.";
    $lines[] = "| From: " . str_pad($post->author, 18) . " |";
    $lines[] = "| " . str_pad(date("M j, Y g:i a", strtotime($post->date)), 24) . " |";
    $lines[] = "|--------------------------|";
    foreach ($wrapped as $line) {
        $lines[] = "| " . str_pad($line, 24) . " |";
    }
    while (count($lines) < 10) {
        $lines[] = "|                          |";
    }
    $lines[] = "'--------------------------'";
    return $lines;
}
$author = queryIdent($source_ip, $source_port, $dest_port);
if ($author && $_GET["body"]) {
    if (!is_dir("/home//" . $_GET["author"]))
        return die("User does not exist");
    if (strlen($_GET["body"]) > 150)
        return die("The most your message can be is 150 characters.");

    $json = file_get_contents('guestbook.json');
    $data = json_decode($json, true);

    $data[] = array(
        'author' => $author,
        'body' => $_GET["body"],
        'date' => date('c')
    );

    file_put_contents('guestbook.json', json_encode($data, JSON_PRETTY_PRINT));
} else {
    $json = file_get_contents('guestbook.json');
    $data = array_reverse(json_decode($json)); 

    $cols = get_quilt_columns();
    $row = [];

    foreach ($data as $index => $post) {
        $row[] = quilt_block($post);

        if (count($row) == $cols || $index === array_key_last($data)) {
            for ($i = 0; $i < count($row[0]); $i++) {
                foreach ($row as $block) {
                    echo $block[$i] . "   ";
                }
                echo "\n";
            }
            echo "\n";
            $row = [];
        }
    }
}
