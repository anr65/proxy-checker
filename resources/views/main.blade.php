<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Checker</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
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
        <div id="results" class="mt-4">
            <h3>Results:</h3>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">IP</th>
                    <th scope="col">Port</th>
                    <th scope="col">Status</th>
                    <th scope="col">Country</th>
                    <th scope="col">City</th>
                    <th scope="col">ISP</th>
                </tr>
                </thead>
                <tbody id="resultsBody"></tbody>
            </table>
            <div id="progressInfo"></div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        $('#proxyForm').submit(function(event) {
            event.preventDefault();

            var submitButton = $('#checkButton');
            submitButton.prop('disabled', true).text('Checking...');

            var progressBar = $('#progressBar');
            progressBar.width('0%').attr('aria-valuenow', 0);

            var intervalId = setInterval(function() {
                $.ajax({
                    type: 'GET',
                    url: '/check-proxies/progress',
                    success: function(response) {
                        updateProgressBar(response.progress);
                        if (response.done) {
                            clearInterval(intervalId);
                            fetchResults(response.job_id);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }, 1000);

            var formData = $(this).serialize();
            $.ajax({
                type: 'POST',
                url: '/check-proxies',
                data: formData,
                success: function(response) {
                    // Job ID returned from backend
                    console.log('Job ID:', response.job_id);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });

        function updateProgressBar(progress) {
            var progressBar = $('#progressBar');
            progressBar.width(progress + '%').attr('aria-valuenow', progress);
            $('#progressInfo').text(progress + '% completed');
        }

        function fetchResults(jobId) {
            $.ajax({
                type: 'GET',
                url: '/check-proxies/progress',
                data: { job_id: jobId },
                success: function(response) {
                    displayResults(response.results);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        }

        function displayResults(results) {
            var resultsBody = $('#resultsBody');
            resultsBody.empty();
            $.each(results, function(index, result) {
                var row = $('<tr>');
                row.append($('<td>').text(result.ip));
                row.append($('<td>').text(result.port));
                row.append($('<td>').text(result.status));
                row.append($('<td>').text(result.country));
                row.append($('<td>').text(result.city));
                row.append($('<td>').text(result.isp));
                resultsBody.append(row);
            });
        }
    });
</script>
</body>
</html>
