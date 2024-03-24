<?php

namespace App\Jobs;

use App\Models\JobsList;
use App\Models\Proxy;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\SerializesModels;

class CheckProxiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $proxy;
    protected $jobId;
    protected $totalProxies;

    /**
     * Create a new job instance.
     */
    public function __construct($proxy, $jobId, $totalProxies)
    {
        $this->proxy = $proxy;
        $this->jobId = $jobId;
        $this->totalProxies = $totalProxies;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $proxyParts = explode(':', $this->proxy);
        $ip = $proxyParts[0];
        $port = $proxyParts[1];

        $client = new Client();
        $response = $client->get("http://ip-api.com/json/{$ip}?fields=country,city,isp");
        $locationData = json_decode($response->getBody()->getContents(), true);

        // Attempt HTTP connection
        $httpSuccess = $this->testConnection('', $ip, $port);
        // Attempt HTTPS connection
        $httpsSuccess = $this->testConnection("s", $ip, $port);
        // Attempt SOCKS connection
        $socksSuccess = $this->testSocksConnection($ip, $port);

        // Determine the type of successful connection

        $country = $locationData['country'] ?? 'Unknown';
        $city = $locationData['city'] ?? 'Unknown';
        $location = "$country/$city";
        $proxyInfo = [
            'ip_port' => "$ip:$port",
            'location' => $location,
            'timeout' => 100,
            'ext_ip' => $ip,
            'job_uuid' => $this->jobId
        ];
        if ($httpSuccess) {
            $proxyInfo['type'] = 'HTTP';
            $proxyInfo['status'] = true;
        } else if ($httpsSuccess) {
            $proxyInfo['type'] = 'HTTPS';
            $proxyInfo['status'] = true;
        } else if ($socksSuccess) {
            $proxyInfo['type'] = 'SOCKS';
            $proxyInfo['status'] = true;
        } else {
            $proxyInfo['type'] = 'Unknown';
            $proxyInfo['status'] = false;
        }

        Proxy::create($proxyInfo);

        $checkLastJob = count(Proxy::where('job_uuid', $this->jobId)->get()) >= $this->totalProxies;
        if ($checkLastJob) {
            $workingCount = count(Proxy::where('job_uuid', $this->jobId)->where('status', true)->get());
            JobsList::where('uuid', $this->jobId)->update(['ended_at' => now(), 'working_count' => $workingCount]);
        }
    }

    private function testConnection($type, $ip, $port)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ip.oxylabs.io/");
        curl_setopt($ch, CURLOPT_PROXY, "http{$type}://{$ip}:{$port}");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_exec($ch);
        if (curl_errno($ch) == 0) {
            return true;
        } else {
            echo false;
        }
        curl_close($ch);
    }

    private function testSocksConnection($ip, $port)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ip.oxylabs.io/"); // URL doesn't matter for SOCKS test
        curl_setopt($ch, CURLOPT_PROXY, "$ip:$port");
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); // Use SOCKS5 proxy
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Timeout in seconds
        curl_exec($ch);
        if (curl_errno($ch) == 0) {
            return true;
        } else {
            echo false;
        }
        curl_close($ch);
    }
}
