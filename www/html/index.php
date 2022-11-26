<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../clipper.php';

define('TMP_DOWNLOAD_DIR', '/tmp/youtube-dl/');

use YoutubeDl\YoutubeDl;
use YoutubeDl\Exception\CopyrightException;
use YoutubeDl\Exception\NotFoundException;
use YoutubeDl\Exception\PrivateVideoException;

if (!isset($_POST['url']) && !isset($_GET['url'])) {
    include 'form.php';
    exit;
}

if (isset($_POST['url'])) {
    $targetUrl = '/?' . http_build_query($_POST);

    //header('Location: ' . '/?' . http_build_query($_POST));
    echo 'Downloading ' . $_POST['url'] . ', please wait...';

    echo '<script>window.location="' . $targetUrl . '";</script>';
    return;
}

// For more options go to https://github.com/rg3/youtube-dl#user-content-options
$url = isset($_GET['url']) ? $_GET['url'] : null;
$type = isset($_GET['downloadType']) ? $_GET['downloadType'] : 'video';
$skip = isset($_GET['skipTo']) ? $_GET['skipTo'] : 0;
$name = isset($_GET['name']) ? $_GET['name'] : null;
$duration = isset($_GET['duration']) ? $_GET['duration'] : null;
$normalize = isset($_GET['normalize']) ? $_GET['normalize'] == 1 : false;
//$destination = '/home/daedeloth/Team Drives/thijs@catlab.be/De Quizfabriek/Quizzes/Edities/Seizoen 5/QW 5.3 Radio Quizfabriek (muziekquiz)/Attachments/Gedownload';
//$destination = '/home/daedeloth/Fragmenten/';

if (!file_exists(TMP_DOWNLOAD_DIR)) {
    mkdir(TMP_DOWNLOAD_DIR);
}

// create a temporary directory
$tmpDir = tempnam(TMP_DOWNLOAD_DIR, 'ytdl-');
if (file_exists($tmpDir)) {
    unlink($tmpDir);
}
mkdir($tmpDir);

$options = \YoutubeDl\Options::create()
    ->continue(true)
    ->downloadPath($tmpDir)
    ->url($url);

/*
if ($type === 'audio') {
    $options->extractAudio(true)
        ->audioFormat('mp3')
        ->audioQuality(0)
        ->output('%(title)s.%(ext)s');
}*/

$dl = new YoutubeDl();
$dl->setBinPath('/usr/local/bin/yt-dlp');

$video = $dl->download($options)->getVideos()[0];

if ($video->getError()) {
    throw new Exception($video->getError());
}

$filename = $video->getFile()->getPathname();

if (!$name) {
    $name = $video->getFile()->getBasename('.' . $video->getFile()->getExtension());
}

clipVideo($filename, $name, $type, $skip, $duration, $normalize);

rrmdir($tmpDir);
