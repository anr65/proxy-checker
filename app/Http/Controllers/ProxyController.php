<?php

namespace App\Http\Controllers;

use App\Models\Proxy;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    public function checkProxies(Request $request)
    {
        $proxies = explode("\n", $request->input('proxies'));

        $results = [];
        $totalProxies = count($proxies);
        $workingProxies = 0;

        foreach ($proxies as $proxy) {
            $proxyInfo = $this->checkProxy($proxy);
            sleep(1);
            if ($proxyInfo['status'] === true) {
                $workingProxies++;
            }
            $results[] = $proxyInfo;
        }

        return response()->json([
            'results' => $results,
            'total_proxies' => $totalProxies,
            'working_proxies' => $workingProxies,
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
        $country = $locationData['country'] ?? 'Unknown';
        $city = $locationData['city'] ?? 'Unknown';
        $location = "$country/$city";
        $proxyInfo = [
            'ip_port' => "$ip:$port",
            'type' => "HTTPS",
            'location' => $location,
            'status' => true,
            'timeout' => 100,
            'ext_ip' => $ip
        ];

        $newProxy = new Proxy($proxyInfo);

        return $newProxy;
    }
}
