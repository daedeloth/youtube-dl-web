<?php

use FFMpeg\Filters\Video\VideoFilterInterface;
use FFMpeg\Format\VideoInterface;
use FFMpeg\Media\Video;

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

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                    rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                else
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
            }
        }
        rmdir($dir);
    }
}

$createdFiles = [];

function clipVideo($path, $targetName, $type, $skip = 0, $duration = 120, $normalize = true)
{
    // create a temporary directory
    $tmpDir = tempnam(TMP_DOWNLOAD_DIR, 'ytdl-');
    if (file_exists($tmpDir)) {
        unlink($tmpDir);
    }
    mkdir($tmpDir);

    try {

        /*
        $options = \YoutubeDl\Options::create()
            ->continue(true)
            ->downloadPath($tmpDir)
            ->url($url);

        $dl = new YoutubeDl();
        $dl->setBinPath('/usr/local/bin/yt-dlp');

        $video = $dl->download($options)->getVideos()[0];

        if ($video->getError()) {
            throw new Exception($video->getError());
        }

        $filename = $video->getFile()->getPathname();

        */

        $sourceFilename = $path;
        if (!file_exists($sourceFilename)) {
            throw new Exception('File not found.');
        }

        $filename = explode('/', $sourceFilename);
        $filename = $filename[count($filename) - 1];

        $targetFilename = $tmpDir . '/' . $filename;
        //copy($sourceFilename, $targetFilename);

        if ($targetName) {
            $name = $targetName;
        } else {
            $name = $filename;
        }

        $extension = explode('.', $filename);
        $extension = $extension[count($extension) - 1];

        $currentFilename = $sourceFilename;

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
            'timeout' => 3600, // The timeout for the underlying process
            'ffmpeg.threads' => 12,   // The number of threads that FFMpeg should use
        ]);

        $finalVideo = $ffmpeg->open($currentFilename);

        // video or audio?
        if ($type === 'audio') {
            $format = new \FFMpeg\Format\Audio\Mp3();
            $format->setAudioKiloBitrate(192);
            $extension = 'mp3';

            $audioFileName = $tmpDir . 'audio.mp3';
            $finalVideo->save($format, $audioFileName);
            $finalVideo = $ffmpeg->open($audioFileName);

            /*
            $format = new \FFMpeg\Format\Audio\Mp3();
            $extension = 'mp3';*/
        } else {
            $format = new FFMpeg\Format\Video\X264('copy');
            //$format->setAudioKiloBitrate(192);
            if ($kiloBitRate) {
                $format->setKiloBitrate($kiloBitRate);
            }
            $extension = 'mp4';
        }

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
        } elseif ($clipDuration) {
            $start = FFMpeg\Coordinate\TimeCode::fromSeconds(0);
            $clipDuration = FFMpeg\Coordinate\TimeCode::fromSeconds($clipDuration);
            $finalVideo->filters()->clip($start, $clipDuration);
        }

        if ($type === 'video-only') {
            $finalVideo->addFilter(new VideoMuteFilter());
        }

        if ($clipStart > 0 && $type === 'audio') {
            $finalVideo->filters()->custom('afade=t=in:st=' . $clipStart . ':d=1');
        }

        $finalDestination = $tmpDir . '/clip.' . $extension;
        $finalVideo->save($format, $finalDestination);

        // Normalize
        if ($normalize) {
            $normalizedFilename = $tmpDir . '/clip-normalized.' . $extension;

            $audioCodexArgument = '-c:a aac';
            if ($type === 'audio') {
                $audioCodexArgument = '-c:a libmp3lame';
            }

            $output = null;
            $status = null;

            $result = exec(
                'ffmpeg-normalize ' . $audioCodexArgument . ' --normalization-type peak --target-level 0 ' . escapeshellarg($finalDestination) . ' -o ' . escapeshellarg($normalizedFilename),
                $output,
                $status
            );

            if ($status == 0) {
                $finalDestination = $normalizedFilename;
            } else {
                error_log('Failed normalizing: "' . $status . '" - "' . $result . '" - ' . implode("\n", $output));
            }
        }

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
    } catch (\YoutubeDl\Exception\YoutubeDlException $e) {
        // Video not found
        echo '<pre>';
        echo $e;
    } catch (\Exception $e) {
        // Failed to download
        echo '<pre>';
        echo $e;
    }

    rrmdir($tmpDir);

}
