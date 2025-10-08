<?php
declare(strict_types=1);

namespace App\Services;

final class IpInfoService
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(string $query): array
    {
        $target = trim($query);
        if ($target === '') {
            return ['error' => 'empty query'];
        }

        // Resolve domain to IP if needed
        if (!filter_var($target, FILTER_VALIDATE_IP)) {
            $resolved = gethostbyname($target);
            if ($resolved === $target) {
                return ['error' => '无法解析域名'];
            }
            $target = $resolved;
        }

        // Use a public IP info API (ip-api.com)
        $url = 'http://ip-api.com/json/' . urlencode($target) . '?lang=zh-CN';
        $client = new HttpClient();
        $resp = $client->request('GET', $url);

        $json = json_decode($resp['body'] ?? '', true);
        if (!is_array($json)) {
            return ['error' => '查询失败'];
        }
        return $json;
    }
}


