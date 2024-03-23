<!-- resources/views/proxy-check.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Checker</title>
</head>
<body>
<div>
    <h1>Proxy Checker</h1>
    <form id="proxyForm">
        @csrf
        <div>
            <label for="proxyList">Proxy List:</label>
            <textarea id="proxyList" name="proxies" rows="10" cols="50"></textarea>
        </div>
        <button type="submit">Check Proxies</button>
    </form>
</div>

<div id="results">
    <!-- Здесь будут отображаться результаты проверки -->
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $('#proxyForm').submit(function(event) {
            event.preventDefault();
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
            });
            resultsDiv.append('</div>');
        }
    });
</script>
</body>
</html>
