<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\HttpClient;

final class ApiTestController
{
    public function run(array $req): string
    {
        $url = trim((string)($req['url'] ?? ''));
        // 固定 POST 方法
        $method = 'POST';
        // 内置请求头
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: check-gpt-php/1.0',
        ];

        // 可选：用户填写的令牌，自动追加 Authorization 头
        $token = trim((string)($req['token'] ?? ''));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        // 内置请求体（仅在需要主体的方法下发送）
        $model = trim((string)($req['model'] ?? 'gpt-3.5-turbo'));
        if ($model === '') {
            $model = 'gpt-3.5-turbo';
        }
        $defaultBodyArray = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'hello from check-gpt-php',
                ],
            ],
        ];
        $body = json_encode($defaultBodyArray, JSON_UNESCAPED_UNICODE);

        $client = new HttpClient();
        $response = $client->request($method, $url, $headers, $body);

        $statusText = 'HTTP ' . $response['status'];
        $logText = "已发送 POST 请求\\n模型: {$model}";
        $prettyBody = (string)$response['body'];
        $decoded = json_decode($prettyBody, true);
        if (is_array($decoded)) {
            $prettyBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $responseText = (string)$response['headers'] . "\n\n" . $prettyBody;

        $apiUrl = $url;
        $title = 'API 测试工具';
        ob_start();
        include __DIR__ . '/../../views/result.php';
        $content = (string)ob_get_clean();
        ob_start();
        include __DIR__ . '/../../views/layout.php';
        return (string)ob_get_clean();
    }
}


