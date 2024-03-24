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

//         Attempt HTTP connection
        $httpSuccess = $this->testConnection("http://google.com", $ip, $port);
        // Attempt HTTPS connection
        $httpsSuccess = $this->testConnection("https://google.com", $ip, $port);
        // Attempt SOCKS connection
//        $socksSuccess = $this->testSocksConnection($ip, $port);

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
        } else {
            $proxyInfo['type'] = 'Unknown';
            $proxyInfo['status'] = false;
        }
//        if ($socksSuccess) {
//            $proxyInfo['type'] = 'SOCKS';
//            $proxyInfo['status'] = true;
//        } else {
//            $proxyInfo['type'] = 'Unknown';
//            $proxyInfo['status'] = false;
//        }

        Proxy::create($proxyInfo);

        $checkLastJob = count(Proxy::where('job_uuid', $this->jobId)->get()) >= $this->totalProxies;
        if ($checkLastJob) {
            $workingCount = count(Proxy::where('job_uuid', $this->jobId)->where('status', true)->get());
            JobsList::where('uuid', $this->jobId)->update(['ended_at' => now(), 'working_count' => $workingCount]);
        }
    }

    private function testConnection($url, $ip, $port)
    {

        $client = new Client([
            'proxy' => "http://$ip:$port", // Replace with your proxy IP and port
            'timeout' => 1, // Set the total timeout in seconds
        ]);

        try {
            $response = $client->request('GET', 'http://example.com');
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                return response()->json([
                    'results' => $statusCode,
                ]);
            } else {
                return response()->json([
                    'results' => $statusCode,
                ]);
            }
        } catch (ConnectException $e) {
            // Check if the timeout exception occurred
            if (strpos($e->getMessage(), 'cURL error 28') !== false) {
                // Timeout occurred, set timeout field to 20000 and return response
                return response()->json([
                    'results' => ['timeout' => 1000],
                ]);
            } else {
                // Other connection exception occurred, return response with error
                return response()->json([
                    'results' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            // Other exceptions occurred, return response with error
            return response()->json([
                'results' => $e->getMessage(),
            ]);
        }
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
}
