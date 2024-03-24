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
        }

        JobsList::create([
            'uuid' => $jobId,
            'started_at' => $started_at,
            'total_count' => $totalProxies,
        ]);

        return response()->json([
            'message' => 'Jobs started',
            'total_proxies' => $totalProxies,
        ]);
    }

    private function checkProxy($proxy, $jobId)
    {
        $proxyParts = explode(':', $proxy);
        $ip = $proxyParts[0];
        $port = $proxyParts[1];

        $client = new Client();
        $response = $client->get("http://ip-api.com/json/{$ip}?fields=country,city,isp");
        $locationData = json_decode($response->getBody()->getContents(), true);

        // Attempt HTTP connection
        $httpSuccess = $this->testConnection("http://google.com", $ip, $port);
        // Attempt HTTPS connection
        $httpsSuccess = $this->testConnection("https://google.com", $ip, $port);
        // Attempt SOCKS connection
        $socksSuccess = $this->testSocksConnection($ip, $port);

        // Determine the type of successful connection

        $country = $locationData['country'] ?? 'Unknown';
        $city = $locationData['city'] ?? 'Unknown';
        $location = "$country/$city";
        $proxyInfo = [
            'ip_port' => "$ip:$port",
            'type' => "",
            'location' => $location,
            'status' => null,
            'timeout' => 100,
            'ext_ip' => $ip,
            'job_uuid' => $jobId
        ];

        if ($httpSuccess) {
            $proxyInfo['type'] = 'HTTP';
            $proxyInfo['status'] = true;
        } elseif ($httpsSuccess) {
            $proxyInfo['type'] = 'HTTPS';
            $proxyInfo['status'] = true;
        } elseif ($socksSuccess) {
            $proxyInfo['type'] = 'SOCKS';
            $proxyInfo['status'] = true;
        } else {
            $proxyInfo['type'] = 'Unkown';
            $proxyInfo['status'] = false;
        }

        Proxy::create($proxyInfo);

        return $proxyInfo;
    }


    private function testConnection($url, $ip, $port)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PROXY, "$ip:$port");
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0.1); // Timeout in seconds
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode == 200); // Check if connection was successful
    }

    private function testSocksConnection($ip, $port)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://example.com"); // URL doesn't matter for SOCKS test
        curl_setopt($ch, CURLOPT_PROXY, "$ip:$port");
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); // Use SOCKS5 proxy
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0.1); // Timeout in seconds
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode == 200); // Check if connection was successful
    }

    public function getProgress(Request $request)
    {
        $jobId = $request->query('uuid');
        $job = JobsList::where('uiud', $jobId)->first();
        if ($job && !is_null($job->ended_at)) {
            $results = Proxy::where('job_uuid', $jobId)->get();
            $workingCount = $job->working_count;
            $totalCount = $job->total_count;
            return response()->json([
                'success' => true,
                'results' => $results,
                'working' => $workingCount,
                'total_proxies' => $totalCount,
            ])->setStatusCode(200);
        } else if ($job && is_null($job->ended_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Job still running',
            ])->setStatusCode(400);
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
}
