<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <title>Download video</title>
</head>
<body>

<!-- Optional JavaScript -->
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
<script src="jqueryFileDownloader.js"></script>

<style>
    .downloading {
        background: #b3fff9;
    }

    .scheduled {
        background: #ffffb3;
    }

    .done {
        background: #bdffbd;
    }

    .error {
        background: #ff7575;
    }

    #downloadList {
        margin-top: 20px;
    }

    #downloadList li span {
        padding: 10px;
        display: block;
    }
</style>

<script>
    var frameCount = 0;
    var activeDownloads = 0;
    var maxActiveDownloads = 2;
    var downloadQueue = [];

    function downloadFile(e) {

        e.preventDefault();
        var url = $('[name=url]').val();
        if (url == '') {
            // do nothing;
            return;
        }

        addPendingDownload(
            url,
            $('[name=name]').val(),
            $('[name=downloadType]').val(),
            $('[name=skipTo]').val(),
            $('[name=duration]').val()
        )

        $('input').val('');
        return false;
    }

    function addPendingDownload(url, name, type, skipTo, duration) {
        if (!url || url == '') {
            return;
        }

        var settings = {
            url: url,
            name: name,
            downloadType: type,
            skipTo: skipTo,
            duration: duration
        };

        var description = url;

        var detail = '';
        if (skipTo) {
            detail += 'start at ' + skipTo + 's, ';
        }

        if (duration) {
            detail += 'duration ' + duration + 's, ';
        }

        if (detail !== '') {
            description += ' (' + detail.substr(0, detail.length - 2) + ')';
        }

        var frameId = frameCount ++;

        var listItem = $('<li><span class="scheduled">Scheduled ' + description + '</span></li>');
        $('#downloadList').append(listItem);

        downloadQueue.push({
            index: frameId,
            listItem: listItem,
            settings: settings,
            url: url,
            description: description
        });

        if (activeDownloads < maxActiveDownloads) {
            downloadNext();
        }
    }

    function downloadNext() {
        if (downloadQueue.length === 0) {
            // done!
            return;
        }

        activeDownloads ++;

        var nextItem = downloadQueue.shift();
        var cookieName = 'download_cookie_' + nextItem.index;

        nextItem.settings.cookieName = cookieName;
        nextItem.listItem.html('<span class="downloading">Downloading ' + nextItem.description + '</span>');

        $.fileDownload(
            '/?' + $.param(nextItem.settings), {

                cookieName: cookieName,
                successCallback: function() {
                    nextItem.listItem.html('<span class="done">Done loading ' + nextItem.description + '</span>');
                    activeDownloads --;

                    downloadNext();
                },

                failCallback: function() {
                    nextItem.listItem.html('<span class="error">Failed downloading ' + nextItem.description + '</span>');
                    activeDownloads --;

                    downloadNext();
                }

            }
        );
    }

    function pasteVideoList(event) {
        let paste = (event.clipboardData || window.clipboardData).getData('text');

        // look for tab or newline
        if (paste.indexOf('\n') < 0 || paste.indexOf('\t') < 0) {
            return;
        }

        event.preventDefault();

        // go through all rows
        var rows = paste.split('\n');
        rows.forEach(function(row) {
            let props = row.split('\t');

            var type = 'video';
            switch (props[2]) {
                case 'audio':
                case 'video':
                case 'video-only':
                    type = props[2];
                    break;
            }

            addPendingDownload(props[0], props[1], type, parseInt(props[3]), parseInt(props[4]));
        });
    }

    window.onbeforeunload = function() {
        if (activeDownloads > 0) {
            return "You have active downloads. Are you sure you want to exit?";
        }
    }
</script>

<div class="container">

    <h1>Download</h1>
    <form onsubmit="return downloadFile(event);" method="post" target="_blank">
        <div class="form-group">
            <label for="url">Video URL (or copy/paste list)</label>
            <input type="url" class="form-control" id="url" name="url" placeholder="URL" onpaste="pasteVideoList(event)" />
        </div>

        <div class="form-group">
            <label for="name">Output file name</label>
            <input type="text" class="form-control" id="name" name="name" placeholder="Name" />
        </div>

        <div class="form-group">
            <label for="downloadType">Convert to</label>
            <select class="form-control" id="downloadType" name="downloadType">
                <option value="audio">Audio only</option>
                <option value="video" selected>Video</option>
                <option value="video-only">Video only</option>
            </select>
        </div>

        <div class="form-group">
            <label for="skipTo">Skip the first ... seconds</label>
            <input type="number" class="form-control" id="skipTo" name="skipTo" placeholder="Skip first seconds" />
        </div>

        <div class="form-group">
            <label for="duration">Duration (in seconds)</label>
            <input type="number" class="form-control" id="duration" name="duration" placeholder="Duration (in seconds)" />
        </div>

        <button type="submit" class="btn btn-primary">Download</button>
    </form>

    <ul id="downloadList"></ul>

    <div id="frameDiv"></div>

    <div class="footer">
        <a href="https://github.com/daedeloth/youtube-dl-web" target="_blank">Source code</a>
    </div>

</div>

</body>
</html>
