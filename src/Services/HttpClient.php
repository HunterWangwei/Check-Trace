<?php
declare(strict_types=1);

namespace App\Services;

final class HttpClient
{
    /**
     * @param string $method
     * @param string $url
     * @param array<int, string> $headers
     * @param string $body
     * @return array{status:int, headers:string, body:string}
     */
    public function request(string $method, string $url, array $headers = [], string $body = ''): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== '') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $raw = (string)curl_exec($ch);
        $info = curl_getinfo($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            return [
                'status' => 0,
                'headers' => '',
                'body' => 'cURL error: ' . $error,
            ];
        }

        $headerSize = (int)($info['header_size'] ?? 0);
        $rawHeaders = substr($raw, 0, $headerSize);
        $bodyStr = substr($raw, $headerSize);

        return [
            'status' => (int)($info['http_code'] ?? 0),
            'headers' => (string)$rawHeaders,
            'body' => (string)$bodyStr,
        ];
    }
}


