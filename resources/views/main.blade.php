<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Checker</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <style>
        body {
            padding-top: 60px;
        }
        .container {
            max-width: 800px;
        }
        #loading {
            display: none;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <a class="navbar-brand" href="#">Proxy Checker by Anar Muradov</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link active" href="#home">Главная</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/proxy-history">История</a>
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
                <label for="proxyList">Список прокси:</label>
                <textarea class="form-control" id="proxyList" name="proxies" rows="10" cols="50"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" id="checkButton">Проверить</button>
            <div id="loading" class="spinner-border text-primary ml-3" role="status">
                <span class="sr-only">Загрузка...</span>
            </div>
        </form>
        <div class="progress mt-3">
            <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progressBar"></div>
        </div>
        <div class="mt-2" id="progressText">0%</div>

        <!-- Display results in a table -->
        <div id="resultsTable" style="display: none;">
            <h2 class="mt-5">Результат</h2>
            <div class="mt-4">
                <p>Всего прокси: <span id="totalProxies"></span></p>
                <p>Рабочих прокси: <span id="workingProxies"></span></p>
            </div>
            <table class="table">
                <thead>
                <tr>
                    <th>Ip:port</th>
                    <th>Тип</th>
                    <th>Расположение</th>
                    <th>Статус</th>
                    <th>Скорость</th>
                    <th>Реальный IP</th>
                </tr>
                </thead>
                <tbody id="resultsBody">
                </tbody>
            </table>
        </div>
    </div>

    <div id="history" style="display: none;">
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script>
    //Здесь отправляем запрос с проксями и забираем uuid задачи
    $(document).ready(function() {
        $('#proxyForm').submit(function(event) {
            event.preventDefault();

            var submitButton = $('#checkButton');
            submitButton.remove();

            var progressBar = $('#progressBar');
            progressBar.css('width', '0%').attr('aria-valuenow', '0');
            var progressText = $('#progressText');
            progressText.text('0%');

            var loading = $('#loading');
            loading.show();
            var jobId;
            var formData = $(this).serialize();
            $.ajax({
                type: 'POST',
                url: '/check-proxies',
                data: formData,
                success: function(response) {
                    jobId = response.uuid
                    //запускаем поллинг с ключом uuid задачи для проверки выполнения задачи
                    pollProgress(jobId);
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                },
            });
        });

        //Поллим с интервалом 3 секунды для обновления результатов
        function pollProgress(jobId) {
            var loading = $('#loading');
            var intervalId = setInterval(function() {
                $.ajax({
                    type: 'GET',
                    url: '/check-proxies/progress',
                    data: { uuid: jobId },
                    success: function(response) {
                        if (response.done === 1) {
                            clearInterval(intervalId);
                            displayResults(response);
                            loading.hide();
                            $('#proxyForm').append('<button type="submit" class="btn btn-primary" id="checkButton">Проверить</button>');
                        } else if (response.done === 0) {
                            updateProgressBar((response.done_count + 1) / data.results.length * 100);
                        } else if (response.status === 500) {
                            clearInterval(intervalId);
                            console.log(response)
                        } // No need to handle 200 status here, success block is already handling it
                    },
                    error: function(xhr, status, error) {
                        console.error('Error checking progress:', error);
                        clearInterval(intervalId);
                        // Handle error case here, maybe show an error message
                    },
                });
            }, 3000);
        }
        // Poll every second

        function displayResults(data) {
            var resultsTable = $('#resultsTable');
            resultsTable.show();

            var resultsBody = $('#resultsBody');
            resultsBody.empty();

            $.each(data.results, function(index, result) {
                var statusText = result.status ? 'Работает' : 'Нерабочий';
                var textColor = result.status ? 'black' : 'white';
                var backgroundColor = result.status ? 'green' : 'red';

                resultsBody.append('<tr>' +
                    '<td>' + result.ip_port + '</td>' +
                    '<td>' + result.type + '</td>' +
                    '<td>' + result.location + '</td>' +
                    '<td style="color: ' + textColor + '; background-color: ' + backgroundColor + ';">' + statusText + '</td>' +
                    '<td>' + result.timeout + ' ms</td>' +
                    '<td>' + result.ext_ip + '</td>' +
                    '</tr>');
                updateProgressBar((index + 1) / data.results.length * 100);
            });

            var totalProxiesSpan = $('#totalProxies');
            totalProxiesSpan.text(data.total_proxies);

            var workingProxiesSpan = $('#workingProxies');
            workingProxiesSpan.text(data.working_proxies);
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
