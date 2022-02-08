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
<script src="./jqueryFileDownloader.js"></script>

<style>
    .downloading {
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
    function downloadFile(e) {

        e.preventDefault();
        var frameId = frameCount ++;
        var cookieName = 'download_cookie_' + frameId;

        var url = $('[name=url]').val();
        var settings = {
            url: url,
            name: $('[name=name]').val(),
            downloadType: $('[name=downloadType]').val(),
            skipTo: $('[name=skipTo]').val(),
            duration: $('[name=duration]').val(),
            cookieName: cookieName
        };

        var listItem = $('<li><span class="downloading">Downloading ' + url + '</span></li>');
        $('#downloadList').append(listItem);

        $.fileDownload(
            '/?' + $.param(settings), {

                cookieName: cookieName,
                successCallback: function() {
                    listItem.html('<span class="done">Done loading ' + url + '</span>');
                },

                failCallback: function() {
                    listItem.html('<span class="error">Failed downloading ' + url + '</span>');
                }

            }
        );

        $('input').val('');
        return false;
    }
</script>

<div class="container">

    <h1>Download</h1>
    <form onsubmit="return downloadFile(event);" method="post" target="_blank">
        <div class="form-group">
            <label for="url">Video URL</label>
            <input type="url" class="form-control" id="url" name="url" placeholder="URL" />
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
            <label for="skipTo">Skip the first seconds</label>
            <input type="number" class="form-control" id="skipTo" name="skipTo" placeholder="Skip first seconds" />
        </div>

        <div class="form-group">
            <label for="duration">Duration</label>
            <input type="number" class="form-control" id="duration" name="duration" placeholder="Duration" />
        </div>

        <button type="submit" class="btn btn-primary">Download</button>
    </form>

    <ul id="downloadList"></ul>

    <div id="frameDiv"></div>

    <div class="footer">
        <a href="https://github.com/daedeloth/youtube-dl-web">Source code</a>
    </div>

</div>

</body>
</html>
