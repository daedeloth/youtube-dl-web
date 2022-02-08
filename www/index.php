<?php


require __DIR__ . '/vendor/autoload.php';

define('TMP_DOWNLOAD_DIR', '/tmp/youtube-dl/');

use FFMpeg\Filters\Video\VideoFilterInterface;
use FFMpeg\Format\VideoInterface;
use FFMpeg\Media\Video;
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
//$destination = '/home/daedeloth/Team Drives/thijs@catlab.be/De Quizfabriek/Quizzes/Edities/Seizoen 5/QW 5.3 Radio Quizfabriek (muziekquiz)/Attachments/Gedownload';
//$destination = '/home/daedeloth/Fragmenten/';

if (!file_exists(TMP_DOWNLOAD_DIR)) {
    mkdir(TMP_DOWNLOAD_DIR);
}


class VideoMuteFilter implements VideoFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Video $video, VideoInterface $format)
    {
        return array('-an');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 1;
    }
}

$createdFiles = [];

try {
    $options = [
        'continue' => true, // force resume of partially downloaded files. By default, youtube-dl will resume downloads if possible.
    ];

    if ($type === 'audio') {
        $options['extract-audio'] = true;
        $options['audio-format'] = 'mp3';
        $options['audio-quality'] = 0;
        $options['output'] = '%(title)s.%(ext)s';
    }

    $dl = new YoutubeDl($options);
    $dl->setDownloadPath(TMP_DOWNLOAD_DIR);

    $video = $dl->download($url);
    $filename = $video->getFilename();

    if (!$name) {
        $name = $filename;
    }

    $extension = explode('.', $filename);
    $extension = $extension[count($extension) - 1];

    $currentFilename = TMP_DOWNLOAD_DIR . $filename;
    $createdFiles[] = $currentFilename;

    //echo '<pre>';
    //echo 'Downloaded ' . $filename . " to " . $currentFilename . "\n";

    $probe = FFMpeg\FFProbe::create();

    $probeInfo = $probe
            ->streams($currentFilename)
            ->videos()
            ->first();

    /*
    var_dump($probeInfo);
    exit;
    */

    $kiloBitRate = null;
    $bitrate = null;

    if ($probeInfo) {
        $bitrate = $probeInfo->get('bitrate');;

        if ($bitrate) {
            $kiloBitRate = ceil($bitrate / 1024);
        } else {
            $width = $probeInfo->get('width');
            if ($width) {
                $kiloBitRate = max(1500, ceil(($width / 1980) * 1500));
            }
        }
    }

    // now cut
    $ffmpeg = FFMpeg\FFMpeg::create([
        'timeout'          => 3600, // The timeout for the underlying process
        'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
    ]);

    $finalVideo = $ffmpeg->open($currentFilename);

    $clipStart = intval($skip);
    $clipDuration = $duration ? intval($duration) : null;

    if ($clipStart) {
        $start = FFMpeg\Coordinate\TimeCode::fromSeconds($clipStart);

        if ($clipDuration > 0) {
            $clipDuration = FFMpeg\Coordinate\TimeCode::fromSeconds($clipDuration);
            $finalVideo->filters()->clip($start, $clipDuration);
        } else {
            $finalVideo->filters()->clip($start);
        }
    }

    // video or audio?
    if ($type === 'audio') {
        $format = new \FFMpeg\Format\Audio\Mp3();
        $extension = 'mp3';
    } else {
        $format = new FFMpeg\Format\Video\X264('aac');

        if ($kiloBitRate) {
            $format->setKiloBitrate($kiloBitRate);
        }

        $extension = 'mp4';
    }

    if ($type === 'video-only') {
        $finalVideo->addFilter(new VideoMuteFilter());
    }

    if ($clipStart > 0 && $type === 'audio') {
        $finalVideo->filters()->custom('afade=t=in:st=' . $clipStart . ':d=1');
    }

    $finalDestination = TMP_DOWNLOAD_DIR . '/' . tmpfile() . '.' . $extension;
    //echo 'Saving to ' . $finalDestination . "\n";

    $finalVideo->save($format, $finalDestination);
    $createdFiles[] = $finalDestination;

    //echo 'Saved to ' . $finalDestination . "\n";

    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $name . '.' . $extension . "\"");

    if (isset($_GET['cookieName'])) {
        setcookie($_GET['cookieName'], 'true');
    }

    readfile($finalDestination);

    // $video->getFile(); // \SplFileInfo instance of downloaded file
} catch (NotFoundException $e) {
    // Video not found
    echo '<pre>';
    echo $e;
} catch (PrivateVideoException $e) {
    // Video is private
    echo '<pre>';
    echo $e;
} catch (CopyrightException $e) {
    // The YouTube account associated with this video has been terminated due to multiple third-party notifications of copyright infringement
    echo '<pre>';
    echo $e;
} catch (\Exception $e) {
    // Failed to download
    echo '<pre>';
    echo $e;
}

foreach ($createdFiles as $v) {
    unlink($v);
}
