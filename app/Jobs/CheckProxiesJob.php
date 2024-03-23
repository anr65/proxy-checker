<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CheckProxiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $proxies;
    protected $totalProxies;
    protected $workingProxies;
    protected $jobId;

    /**
     * Create a new job instance.
     *
     * @param array $proxies
     * @return void
     */
    public function __construct(array $proxies)
    {
        $this->proxies = $proxies;
        $this->totalProxies = count($proxies);
        $this->workingProxies = 0;
        $this->jobId = Str::uuid(); // Generate a unique job ID
        Cache::put('proxy_check_' . $this->jobId, [
            'progress' => 0,
            'total_proxies' => $this->totalProxies,
            'working_proxies' => $this->workingProxies,
            'done' => false, // Mark job as not done initially
            'results' => [], // Initialize results array
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->proxies as $proxy) {
            // Logic for checking each proxy
            $proxyInfo = $this->checkProxy($proxy);
            if ($proxyInfo['status'] === 'working') {
                $this->workingProxies++;
            }
            // Update progress and results in cache
            $progress = ($this->workingProxies / $this->totalProxies) * 100;
            $this->updateProgress($progress);
            $this->updateResults($proxyInfo);
        }
        // Mark job as done
        Cache::put('proxy_check_' . $this->jobId . '_done', true);
    }

    /**
     * Check the status of a single proxy.
     *
     * @param string $proxy
     * @return array
     */
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

    /**
     * Update the progress in the cache.
     *
     * @param float $progress
     * @return void
     */
    private function updateProgress($progress)
    {
        Cache::put('proxy_check_' . $this->jobId, [
            'progress' => $progress,
            'total_proxies' => $this->totalProxies,
            'working_proxies' => $this->workingProxies,
            'done' => false, // Keep marking job as not done
            'results' => Cache::get('proxy_check_' . $this->jobId)['results'], // Preserve existing results
        ]);
    }

    /**
     * Update the results in the cache.
     *
     * @param array $proxyInfo
     * @return void
     */
    private function updateResults($proxyInfo)
    {
        $results = Cache::get('proxy_check_' . $this->jobId)['results'];
        $results[] = $proxyInfo;
        Cache::put('proxy_check_' . $this->jobId, [
            'progress' => Cache::get('proxy_check_' . $this->jobId)['progress'], // Preserve existing progress
            'total_proxies' => $this->totalProxies,
            'working_proxies' => $this->workingProxies,
            'done' => false, // Keep marking job as not done
            'results' => $results,
        ]);
    }

    /**
     * Get the job ID.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->jobId;
    }
}
