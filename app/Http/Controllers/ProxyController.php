<?php

namespace App\Http\Controllers;

use App\Jobs\CheckProxiesJob;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    public function checkProxies(Request $request)
    {
        $proxies = explode("\n", $request->input('proxies'));

        $job = new CheckProxiesJob($proxies);
        CheckProxiesJob::dispatch($job);

        return response()->json([
            'message' => 'Proxy checking job has been dispatched successfully.',
            'job_id' => $job->getJobId(), // Return the job ID
            'done' => false, // Initially mark job as not done
        ]);
    }

    private function checkProxy($proxy)
    {
        $proxyParts = explode(':', $proxy);
        $ip = $proxyParts[0];
        $port = $proxyParts[1];

        $client = new Client();
        $response = $client->get("http://ip-api.com/json/{$ip}?fields=country,city,isp");
        $locationData = json_decode($response->getBody()->getContents(), true);

        $proxyInfo = [
            'ip' => $ip,
            'port' => $port,
            'status' => 'working',
            'country' => $locationData['country'] ?? 'Unknown',
            'city' => $locationData['city'] ?? 'Unknown',
            'isp' => $locationData['isp'] ?? 'Unknown'
        ];

        return $proxyInfo;
    }
}
