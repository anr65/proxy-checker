<!-- resources/views/proxy-history.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proxy Checker - История</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
                <a class="nav-link" href="/">Главная</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="/proxy-history">История</a> <!-- Add link to История page -->
            </li>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <h1>История</h1>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Начало</th>
            <th>Конец</th>
            <th>Всего</th>
            <th>Рабочих</th>
        </tr>
        </thead>
        <tbody id="historyTable">
        <!-- Job history will be populated here dynamically -->
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="proxyModal" tabindex="-1" role="dialog" aria-labelledby="proxyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proxyModalLabel">Прокси для задания ID: <span id="modalJobId"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
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
                    <tbody id="modalResultsBody">
                    <!-- Proxy results will be populated here dynamically -->
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
    $(document).ready(function() {
        // Load job history on page load
        loadJobHistory();

        // Function to load job history
        function loadJobHistory() {
            $.get('/done-jobs', function(response) {
                var historyTable = $('#historyTable');
                historyTable.empty();

                $.each(response.list, function(index, job) {
                    var row = $('<tr>').append(
                        $('<td>').text(job.id),
                        $('<td>').text(job.started_at),
                        $('<td>').text(job.ended_at),
                        $('<td>').text(job.total_count),
                        $('<td>').text(job.working_count)
                    ).click(function() {
                        openProxyModal(job.uuid);
                    });

                    historyTable.append(row);
                });
            });
        }

        // Function to open proxy modal and fetch proxy data
        function openProxyModal(jobId) {
            $('#modalJobId').text(jobId);
            $('#modalResultsBody').empty();

            $.get('/get-job-proxies', { job_id: jobId }, function(response) {
                $.each(response.results, function(index, result) {
                    var row = $('<tr>').append(
                        $('<td>').text(result.ip_port),
                        $('<td>').text(result.type),
                        $('<td>').text(result.location),
                        $('<td>').text(result.status),
                        $('<td>').text(result.timeout + ' ms'),
                        $('<td>').text(result.ext_ip)
                    );

                    $('#modalResultsBody').append(row);
                });
            });

            $('#proxyModal').modal('show');
        }
    });
</script>
</body>
</html>
