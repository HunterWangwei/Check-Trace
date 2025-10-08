<?php
declare(strict_types=1);

namespace App\Services;

final class TraceService
{
    private ?string $sessionId = null;
    /** @var array<int, array{t:string, ua:string, xff:string, ip:string, kind:string}> */
    private array $logs = [];

    public function beginSession(): string
    {
        $this->sessionId = bin2hex(random_bytes(8));
        $this->logs = [];
        // 初始化对应日志文件
        $file = $this->getLogFile($this->sessionId);
        @file_put_contents($file, "");
        return $this->sessionId;
    }

    public function getPublicBaseUrl(): string
    {
        // 假定本机/域名直接可访问当前 index.php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }

    public function recordHit(string $id, string $ua, string $xff, string $ip, string $kind): void
    {
        $line = sprintf("%s\t%s\t%s\t%s\t%s\n", date('H:i:s'), $ua, $kind, $xff, $ip);
        @file_put_contents($this->getLogFile($id), $line, FILE_APPEND);
    }

    public function formatLogs(?string $id = null): string
    {
        $useId = $id ?: $this->sessionId;
        if (!$useId) {
            return '暂无访问记录，等待节点请求图片...';
        }
        $file = $this->getLogFile($useId);
        if (!is_file($file)) {
            return '暂无访问记录，等待节点请求图片...';
        }
        $content = (string)@file_get_contents($file);
        if ($content === '') {
            return '暂无访问记录，等待节点请求图片...';
        }
        $rows = [];
        $uniqueIps = [];
        foreach (explode("\n", trim($content)) as $raw) {
            if ($raw === '') { continue; }
            [$t, $ua, $kind, $xff, $ip] = array_pad(explode("\t", $raw), 5, '');
            if ($ip === '') { continue; }
            if (!isset($uniqueIps[$ip])) {
                $uniqueIps[$ip] = true;
                $rows[] = $ip;
            }
        }

        if (empty($rows)) {
            return '暂无访问记录，等待节点请求图片...';
        }

        // 查询 IP 信息并格式化输出
        $ipSvc = new \App\Services\IpInfoService();
        $out = [];
        $out[] = '正在检测中转节点...';
        foreach ($rows as $ip) {
            $info = $ipSvc->lookup($ip);
            $org = '';
            if (is_array($info)) {
                $org = (string)($info['org'] ?? '');
                if ($org === '' && isset($info['isp'])) {
                    $org = (string)$info['isp'];
                }
                $city = (string)($info['city'] ?? '');
                $regionName = (string)($info['regionName'] ?? '');
                $countryCode = (string)($info['countryCode'] ?? '');
                $place = $city !== '' ? $city : $regionName;
                $dc = strtoupper(substr($place !== '' ? $place : ($countryCode !== '' ? $countryCode : 'DC'), 0, 3));
                $loc = ($place !== '' ? $place : 'Unknown') . ', ' . ($countryCode !== '' ? $countryCode : '--');
                $orgText = $org !== '' ? $org : 'Unknown Org';
                $out[] = sprintf('检测到中转节点: %s (%s) - 位置: %s [DC: %s]', $orgText, $ip, $loc, $dc);
            } else {
                $out[] = sprintf('检测到中转节点: Unknown Org (%s) - 位置: Unknown, -- [DC: DC]', $ip);
            }
        }

        return implode("\n", $out);
    }

    private function getLogFile(string $id): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'checkgpt_trace_' . $id . '.log';
    }
}


