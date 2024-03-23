<?php

namespace App\Http\Controllers;

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
            if ($proxyInfo['status'] === 'working') {
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://example.com'); // Замените example.com на адрес, который вы хотите запросить через прокси
        curl_setopt($ch, CURLOPT_PROXY, $ip);
        curl_setopt($ch, CURLOPT_PROXYPORT, $port);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Опционально: установка таймаута для запроса
        $response = curl_exec($ch);
        $errorCode = curl_errno($ch);
        curl_close($ch);

        $proxyInfo = [
            'ip' => $ip,
            'port' => $port,
            'status' => 'not working',
            'type' => '', // Вы можете определить тип прокси здесь, если это необходимо
            'country' => '', // Если требуется, определите страну и город прокси
            'city' => '',
            'download_speed' => '', // Дополнительные данные о прокси, такие как скорость скачивания, могут быть добавлены здесь
            'external_ip' => ''
        ];

        if ($errorCode == 0) {
            $proxyInfo['status'] = 'working';
        }

        return $proxyInfo;
    }
}
