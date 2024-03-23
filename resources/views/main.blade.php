<!-- resources/views/proxy-check.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Checker</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 60px;
        }
        .container {
            max-width: 800px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <a class="navbar-brand" href="#">Proxy Checker</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link active" href="#home">Главная</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#history">История</a>
            </li>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <div id="home">
        <h1 class="mb-4">Proxy Checker</h1>
        <form id="proxyForm">
            @csrf
            <div class="form-group">
                <label for="proxyList">Proxy List:</label>
                <textarea class="form-control" id="proxyList" name="proxies" rows="10" cols="50"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" id="checkButton">Check Proxies</button>
        </form>
        <div class="progress mt-3">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progressBar"></div>
        </div>
        <div class="mt-2" id="progressText">0%</div>
    </div>

    <div id="history" style="display: none;">
        <!-- Place to display history -->
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        $('#proxyForm').submit(function(event) {
            event.preventDefault();

            // Disable the button and change its text to "Checking..."
            var submitButton = $('#proxyForm button[type="submit"]');
            submitButton.prop('disabled', true).text('Checking...');

            // Reset progress bar
            var progressBar = $('#progressBar');
            progressBar.css('width', '0%').attr('aria-valuenow', '0');
            var progressText = $('#progressText');
            progressText.text('0%');

            var formData = $(this).serialize();
            $.ajax({
                type: 'POST',
                url: '/check-proxies',
                data: formData,
                success: function(response) {
                    displayResults(response);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        function displayResults(data) {
            var resultsDiv = $('#results');
            resultsDiv.empty();
            resultsDiv.append('<h2>Results</h2>');
            resultsDiv.append('<p>Total Proxies: ' + data.total_proxies + '</p>');
            resultsDiv.append('<p>Working Proxies: ' + data.working_proxies + '</p>');
            resultsDiv.append('<div>');
            $.each(data.results, function(index, result) {
                resultsDiv.append('<p>IP: ' + result.ip + ':' + result.port + ' - Status: ' + result.status + '</p>');
                updateProgressBar((index + 1) / data.results.length * 100);
            });
            resultsDiv.append('</div>');

            // Re-enable the button and change its text back to "Check Proxies"
            var submitButton = $('#proxyForm button[type="submit"]');
            submitButton.prop('disabled', false).text('Check Proxies');
        }

        function updateProgressBar(percentage) {
            // Update progress bar width and aria-valuenow attribute
            var progressBar = $('#progressBar');
            progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);

            // Update progress text
            var progressText = $('#progressText');
            progressText.text(percentage.toFixed(0) + '%');
        }
    });
</script>
</body>
</html>
