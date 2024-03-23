<?php

namespace App\Http\Controllers;

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

        $results = [];
        $totalProxies = count($proxies);
        $workingProxies = 0;
        $jobId = Str::uuid();
        $started_at = now();
        foreach ($proxies as $proxy) {
            $proxyInfo = $this->checkProxy($proxy, $jobId);
            if ($proxyInfo['status'] === true) {
                $workingProxies++;
            }
            $results[] = $proxyInfo;
            $ended_at = now();
        }
        JobsList::create([
            'uuid' => $jobId,
            'started_at' => $started_at,
            'ended_at' => $ended_at,
            'total_count' => $totalProxies,
            'working_count' => $workingProxies
        ]);

        return response()->json([
            'results' => $results,
            'total_proxies' => $totalProxies,
            'working_proxies' => $workingProxies,
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
        $country = $locationData['country'] ?? 'Unknown';
        $city = $locationData['city'] ?? 'Unknown';
        $location = "$country/$city";
        $proxyInfo = [
            'ip_port' => "$ip:$port",
            'type' => "HTTPS",
            'location' => $location,
            'status' => true,
            'timeout' => 100,
            'ext_ip' => $ip,
            'job_id' => $jobId
        ];

        return $proxyInfo;
    }

    public function getDoneJobs() {
        $doneJobs = JobsList::all();
        return response()->json([
            'list' => $doneJobs,
        ]);
    }
}
