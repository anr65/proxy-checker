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

    // Переменные для хранения данных о прокси, задаче и общем количестве прокси
    protected $proxy;
    protected $jobId;
    protected $totalProxies;

    /**
     * Создание нового экземпляра задания.
     */
    public function __construct($proxy, $jobId, $totalProxies)
    {
        $this->proxy = $proxy;
        $this->jobId = $jobId;
        $this->totalProxies = $totalProxies;
    }

    /**
     * Выполнение задания.
     */
    public function handle(): void
    {
        // Разбиваем данные прокси на IP и порт
        $proxyParts = explode(':', $this->proxy);
        $ip = $proxyParts[0];
        $port = $proxyParts[1];

        // Создаем новый HTTP-клиент
        $client = new Client();
        // Запрос к сервису для определения местоположения по IP
        $response = $client->get("http://ip-api.com/json/{$ip}?fields=country,city,isp");
        $locationData = json_decode($response->getBody()->getContents(), true);

        // Попытка HTTP-подключения
        $httpSuccess = $this->testConnection('', $ip, $port);
        // Попытка HTTPS-подключения
        $httpsSuccess = $this->testConnection("s", $ip, $port);
        // Попытка подключения по SOCKS
        $socksSuccess = $this->testSocksConnection($ip, $port);

        // Определение типа успешного подключения
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

        // Сохранение информации о прокси в базу данных
        Proxy::create($proxyInfo);

        // Проверка завершения задачи
        $checkLastJob = count(Proxy::where('job_uuid', $this->jobId)->get()) >= $this->totalProxies;
        if ($checkLastJob) {
            // Обновление статуса задачи и количества рабочих прокси
            $workingCount = count(Proxy::where('job_uuid', $this->jobId)->where('status', true)->get());
            JobsList::where('uuid', $this->jobId)->update(['ended_at' => now(), 'working_count' => $workingCount]);
        }
    }

    // Функция для тестирования HTTP-подключения
    private function testConnection($type, $ip, $port): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ip.oxylabs.io/");
        curl_setopt($ch, CURLOPT_PROXY, "http{$type}://{$ip}:{$port}");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_exec($ch);
        curl_close($ch);
        if (curl_errno($ch) == 0) {
            return true;
        } else {
            return false;
        }
    }

    // Функция для тестирования подключения по SOCKS
    private function testSocksConnection($ip, $port): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ip.oxylabs.io/"); // URL не имеет значения для теста SOCKS
        curl_setopt($ch, CURLOPT_PROXY, "$ip:$port");
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); // Использовать прокси SOCKS5
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // Тайм-аут в секундах
        curl_exec($ch);
        curl_close($ch);
        if (curl_errno($ch) == 0) {
            return true;
        } else {
            return false;
        }
    }
}
