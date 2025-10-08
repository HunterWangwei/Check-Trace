<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\IpInfoService;

final class IpInfoController
{
    public function showForm(): string
    {
        $title = 'IP 查询';
        ob_start();
        ?>
        <h2>IP 信息查询</h2>
        <form method="post" action="?r=/ipinfo">
          <label>IP 或域名</label>
          <input type="text" name="q" placeholder="8.8.8.8 或 example.com" required />
          <div style="margin-top:12px"><button type="submit">查询</button></div>
        </form>
        <?php
        $content = (string)ob_get_clean();
        ob_start();
        include __DIR__ . '/../../views/layout.php';
        return (string)ob_get_clean();
    }

    public function lookup(array $req): string
    {
        $q = trim((string)($req['q'] ?? ''));
        $service = new IpInfoService();
        $result = $service->lookup($q);

        $title = 'IP 查询结果';
        ob_start();
        ?>
        <h2>IP 查询结果</h2>
        <pre><?= htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?></pre>
        <p><a href="?r=/ipinfo">返回</a></p>
        <?php
        $content = (string)ob_get_clean();
        ob_start();
        include __DIR__ . '/../../views/layout.php';
        return (string)ob_get_clean();
    }
}


