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
    public function checkProxies(Request $request)
    {
        $proxies = explode("\n", $request->input('proxies'));

        $jobId = Str::uuid();
        $totalProxies = count($proxies);
        $started_at = now();

        foreach ($proxies as $proxy) {
            CheckProxiesJob::dispatch($proxy, $jobId, $totalProxies);
            sleep(0.6);
        }

        JobsList::create([
            'uuid' => $jobId,
            'started_at' => $started_at,
            'total_count' => $totalProxies,
        ]);

        return response()->json([
            'message' => 'Jobs started',
            'total_proxies' => $totalProxies,
            'uuid' => $jobId,
        ]);
    }
    public function getProgress(Request $request)
    {
        $jobId = $request->query('uuid');
        $job = JobsList::where('uuid', $jobId)->first();
        if ($job && !is_null($job->ended_at)) {
            $results = Proxy::where('job_uuid', $jobId)->get();
            $workingCount = $job->working_count;
            $totalCount = $job->total_count;
            return response()->json([
                'success' => true,
                'results' => $results,
                'working' => $workingCount,
                'total_proxies' => $totalCount,
                'done' => 1,
            ])->setStatusCode(200);
        } else if ($job && is_null($job->ended_at)) {
            $results = Proxy::where('job_uuid', $jobId)->get();
            $totalCount = $job->total_count;
            return response()->json([
                'success' => true,
                'message' => 'Job still running',
                'done' => 0,
                'done_count' => count($results),
                'total_count' => $totalCount
            ])->setStatusCode(200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ])->setStatusCode(500);
        }
    }
    public function getDoneJobs() {
        $doneJobs = JobsList::all();
        return response()->json([
            'list' => $doneJobs,
        ]);
    }

    public function getProxiesByJob(Request $request) {
        $jobData = Proxy::where('job_uuid', $request->query('job_id'))->get();
        return response()->json([
            'results' => $jobData,
        ]);
    }

    public function testConnection(Request $request)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ip.oxylabs.io/");
        curl_setopt($ch, CURLOPT_PROXY, "http://82.223.121.72:39434");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_exec($ch);
        if (curl_errno($ch) == 0) {
            return response()->json([
                'results' => 'SUCCESS',
            ]);
        }
        else if (curl_errno($ch) == 28) {
            return response()->json([
                'results' => 'TIMEOUT',
            ]);
        } else {
            echo "cURL error: " . curl_error($ch);
        }
        curl_close($ch);
    }
}
