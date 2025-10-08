<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\TraceService;
use App\Services\UaClassifier;
use App\Services\HttpClient;

final class TraceController
{
    private TraceService $svc;

    public function __construct()
    {
        $this->svc = new TraceService();
    }

    public function form(): string
    {
        $title = '链路检测';
        ob_start();
        ?>
        <div class="two-col">
          <div class="card">
            <div class="title">API 中转链路检测</div>
            <form method="post" action="?r=/trace&ajax=1" onsubmit="return window.traceSubmit(event)">
              <div>
                <label>API 域名（无需填写 /v1/chat/completions）</label>
                <input type="url" name="url" placeholder="https://api.example.com" required />
              </div>
              <div style="margin-top:12px">
                <label>API Key</label>
                <input type="text" name="token" placeholder="sk-..." />
              </div>
              <div style="margin-top:12px">
                <label>模型</label>
                <input type="text" name="model" value="gpt-4o" />
              </div>
              <div style="margin-top:16px">
                <button type="submit">开始检测</button>
              </div>
              <div class="section-title" style="margin-top:12px">临时图片 URL</div>
              <pre id="img-url" class="muted">提交后生成</pre>
              <div class="section-title" style="margin-top:12px">检测日志</div>
              <pre id="trace-logs" class="muted">等待开始...</pre>
            </form>
          </div>
          <div class="card">
            <div class="title">检测结果</div>
            <pre id="trace-result" class="muted">等待请求...</pre>
          </div>
        </div>
        <script>
        window.traceSubmit = async function(e){
          e.preventDefault();
          const form = e.target;
          const btn = form.querySelector('button[type="submit"]');
          const fd = new FormData(form);
          // UI 反馈：禁用按钮 + 改文案 + 光标等待，并清空旧内容
          if (btn){ btn.disabled = true; btn.textContent = '检测中...'; }
          const $img = document.getElementById('img-url');
          const $logs = document.getElementById('trace-logs');
          const $res  = document.getElementById('trace-result');
          if ($img) $img.textContent = '生成临时图片 URL 中...';
          if ($logs) $logs.textContent = '正在检测中，请稍候...';
          if ($res)  $res.textContent  = '正在请求 API ...';
          document.body.style.cursor = 'progress';

          let data;
          try{
            const resp = await fetch(form.action, {method:'POST', body:fd});
            data = await resp.json();
          }catch(err){
            if ($res) $res.textContent = '请求失败：' + (err && err.message ? err.message : '网络错误');
          }

          if (data){
            if ($img) $img.textContent = (data.imgUrl || '') + (data.status? ('\nHTTP '+data.status):'');
            if ($res) $res.textContent = (data.headers || '') + "\n\n" + (data.body || '');
          }
          // 恢复
          if (btn){ btn.disabled = false; btn.textContent = '开始检测'; }
          document.body.style.cursor = 'default';
          if (data.imgId){
            const id = data.imgId;
            if (window._traceTimer) clearInterval(window._traceTimer);
            if (window._traceTimeout) clearTimeout(window._traceTimeout);
            window._traceTimer = setInterval(async ()=>{
              const r = await fetch('?r=/trace-logs&id='+encodeURIComponent(id));
              const j = await r.json();
              document.getElementById('trace-logs').textContent = j.text || '';
            }, 1000);
            // 自动停止轮询：20 秒后停止，避免无休止请求
            window._traceTimeout = setTimeout(()=>{ try{ clearInterval(window._traceTimer); }catch(e){} }, 20000);
          }
          return false;
        }
        </script>
        <?php
        $content = (string)ob_get_clean();
        ob_start();
        include __DIR__ . '/../../views/layout.php';
        return (string)ob_get_clean();
    }

    public function start(array $req): string
    {
        if ((string)($_GET['ajax'] ?? '') === '1') {
            return $this->startAjax($req);
        }
        $urlInput = trim((string)($req['url'] ?? ''));
        $token = trim((string)($req['token'] ?? ''));
        $model = trim((string)($req['model'] ?? 'gpt-4o'));

        // 规范化：仅填域名时自动补全为 https://domain/v1/chat/completions
        $url = $urlInput;
        if ($url !== '') {
            if (strpos($url, '://') === false) {
                $url = 'https://' . ltrim($url, '/');
            }
            $parts = parse_url($url);
            $path = $parts['path'] ?? '';
            if ($path === '' || $path === '/') {
                $url = rtrim($url, '/') . '/v1/chat/completions';
            }
        }

        $imgId = $this->svc->beginSession();
        $publicUrl = $this->svc->getPublicBaseUrl();
        $imgUrl = rtrim($publicUrl, '/') . '/?r=/img&id=' . urlencode($imgId);

        // 发送带图片的聊天请求（OpenAI 风格）
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: check-gpt-php/1.0',
        ];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $body = json_encode([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [ 'type' => 'text', 'text' => 'what is the number? (captcha)' ],
                        [ 'type' => 'image_url', 'image_url' => [ 'url' => $imgUrl ] ],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE);

        $client = new HttpClient();
        $resp = $client->request('POST', $url, $headers, (string)$body);

        // 汇总日志与结果
        $statusText = '已发起检测。图片URL: ' . $imgUrl . "\n" . 'HTTP ' . $resp['status'];
        $logText = $this->svc->formatLogs($imgId);
        $prettyBody = (string)$resp['body'];
        $decoded = json_decode($prettyBody, true);
        if (is_array($decoded)) {
            $prettyBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $responseText = (string)$resp['headers'] . "\n\n" . $prettyBody;

        $apiUrl = $url;
        $tokenValue = $token;
        $title = 'API 中转链路检测';
        ob_start();
        include __DIR__ . '/../../views/result.php';
        $content = (string)ob_get_clean();
        ob_start();
        include __DIR__ . '/../../views/layout.php';
        return (string)ob_get_clean();
    }

    private function startAjax(array $req): string
    {
        header('Content-Type: application/json');
        $urlInput = trim((string)($req['url'] ?? ''));
        $token = trim((string)($req['token'] ?? ''));
        $model = trim((string)($req['model'] ?? 'gpt-4o'));

        // 规范化 URL（仅域名自动补齐路径）
        $url = $urlInput;
        if ($url !== '') {
            if (strpos($url, '://') === false) {
                $url = 'https://' . ltrim($url, '/');
            }
            $parts = parse_url($url);
            $path = $parts['path'] ?? '';
            if ($path === '' || $path === '/') {
                $url = rtrim($url, '/') . '/v1/chat/completions';
            }
        }

        $imgId = $this->svc->beginSession();
        $publicUrl = $this->svc->getPublicBaseUrl();
        $imgUrl = rtrim($publicUrl, '/') . '/?r=/img&id=' . urlencode($imgId);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: check-gpt-php/1.0',
        ];
        if ($token !== '') { $headers[] = 'Authorization: Bearer ' . $token; }
        $body = json_encode([
            'model' => $model,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    [ 'type' => 'text', 'text' => 'what is the number? (captcha)' ],
                    [ 'type' => 'image_url', 'image_url' => [ 'url' => $imgUrl ] ],
                ],
            ]],
        ], JSON_UNESCAPED_UNICODE);

        $client = new HttpClient();
        $resp = $client->request('POST', $url, $headers, (string)$body);

        $prettyBody = (string)$resp['body'];
        $decoded = json_decode($prettyBody, true);
        if (is_array($decoded)) {
            $prettyBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            'ok' => true,
            'imgId' => $imgId,
            'imgUrl' => $imgUrl,
            'status' => (int)$resp['status'],
            'headers' => (string)$resp['headers'],
            'body' => $prettyBody,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function logs(array $req): void
    {
        header('Content-Type: application/json');
        $id = trim((string)($req['id'] ?? ''));
        echo json_encode([
            'text' => $this->svc->formatLogs($id),
        ], JSON_UNESCAPED_UNICODE);
    }

    public function image(array $req): void
    {
        $id = (string)($req['id'] ?? '');
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        $classifier = new UaClassifier();
        $kind = $classifier->classify($ua);

        $this->svc->recordHit($id, $ua, $xff, $ip, $kind);

        // 返回真实图片：若本地缓存不存在则下载一张示例图片到临时目录
        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $imgFile = $tmpDir . DIRECTORY_SEPARATOR . 'checkgpt_sample_image.jpg';

        if (!is_file($imgFile) || filesize($imgFile) < 2048) {
            $remote = 'https://picsum.photos/512'; // 随机 512x512 图片
            $ctx = stream_context_create([
                'http' => [ 'timeout' => 10 ],
                'https' => [ 'timeout' => 10 ],
            ]);
            $bin = @file_get_contents($remote, false, $ctx);
            if ($bin !== false && strlen($bin) > 2048) {
                @file_put_contents($imgFile, $bin);
            }
        }

        if (is_file($imgFile)) {
            $mime = 'image/jpeg';
            if (function_exists('finfo_open')) {
                $f = finfo_open(FILEINFO_MIME_TYPE);
                if ($f) {
                    $det = finfo_file($f, $imgFile);
                    if (is_string($det) && $det !== '') { $mime = $det; }
                    finfo_close($f);
                }
            }
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . (string)filesize($imgFile));
            readfile($imgFile);
            return;
        }

        // 兜底：仍返回 PNG（极小概率触发）
        header('Content-Type: image/png');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAACXBIWXMAAAsSAAALEgHS3X78AAABbElEQVRYCe2WwU3DMBRGbyR2J8ADYB2wB2gGqgZ0gRlAB2gAdoA9gF8KXQ2m0s3m8p3m0m3m3jJg8r3Zy6j1Cw3w9v4k7m2Q1E0o9qk3w2QkR3j9xkQnQmK3x1k3bS9QyYk3Qf3n1u2o2w6lUQ2r8G+0qYw8RBBgk9o0b3XQ8hXrQ4Q+fJqjGmQ5+o3kD8GQ0f8w1q3kzC2e9H0WJpB5i2gF8mW3Q2o2y1l1Wg0wG5WgQb2lqkq5bJmYwYpZ2m2z3sQbC1mP8d3V8iG0b7S0t4b4+q0e3jz5yqg6n5kY1kQGq3y3wQH7gX9wQf7kP3gQH7gX9wQf7kP3gQF6m3YVJQJvGQk6Yy7i5q6gwwv4H5xw9dXw3F1m3tYjDd2tq3o2HcGv9K2r8J0vD0qkqTn8m8i3Jr0qkqTv8j8g3Fr0qkqTn8m8i3Jr0qkqTn8iYpYw9mE7S9iZs8AAAAAElFTkSuQmCC');
        echo $png;
    }
}


