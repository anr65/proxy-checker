<?php

namespace App\Http\Controllers;

use App\Jobs\CheckProxiesJob;
use App\Models\JobsList;
use App\Models\Proxy;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProxyController extends Controller
{
    // Метод для проверки списка прокси-серверов
    public function checkProxies(Request $request)
    {
        // Разбиваем входные данные на массив прокси
        $proxies = explode("\n", $request->input('proxies'));

        // Генерируем уникальный идентификатор задачи
        $jobId = Str::uuid();
        $totalProxies = count($proxies);
        $started_at = now();

        // Запускаем задачи проверки прокси-серверов в фоне
        foreach ($proxies as $index => $proxy) {
            CheckProxiesJob::dispatch($proxy, $jobId, $totalProxies)->delay(now()->addSeconds($index));
        }

        // Записываем информацию о задаче в базу данных
        JobsList::create([
            'uuid' => $jobId,
            'started_at' => $started_at,
            'total_count' => $totalProxies,
        ]);

        // Возвращаем ответ с информацией о запущенных задачах
        return response()->json([
            'message' => 'Задачи запущены',
            'total_proxies' => $totalProxies,
            'uuid' => $jobId,
        ]);
    }

    // Метод для получения прогресса выполнения задачи
    public function getProgress(Request $request)
    {
        $jobId = $request->query('uuid');
        $job = JobsList::where('uuid', $jobId)->first();
        if ($job) {
            $workingCount = $job->working_count ?? null;
            $totalCount = $job->total_count ?? null;
            $results = Proxy::where('job_uuid', $jobId)->get();
            if (!is_null($job->ended_at)) {
                return response()->json([
                    'success' => true,
                    'results' => $results,
                    'working' => $workingCount,
                    'total_proxies' => $totalCount,
                    'done' => 1,
                ])->setStatusCode(200);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'Задача еще выполняется',
                    'done' => 0,
                    'done_count' => count($results),
                    'total_count' => $totalCount
                ])->setStatusCode(200);
            }
        } else {
            // Если задача не найдена, возвращаем ошибку
            return response()->json([
                'success' => false,
                'message' => 'Задача не найдена'
            ])->setStatusCode(500);
        }
    }

    // Метод для получения списка завершенных задач
    public function getDoneJobs() {
        $doneJobs = JobsList::all();
        return response()->json([
            'list' => $doneJobs,
        ]);
    }

    // Метод для получения прокси по идентификатору задачи
    public function getProxiesByJob(Request $request) {
        $jobData = Proxy::where('job_uuid', $request->query('job_id'))->get();
        return response()->json([
            'results' => $jobData,
        ]);
    }
}
