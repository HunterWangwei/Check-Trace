<?php
declare(strict_types=1);

/** @var string $apiUrl */
/** @var string $model */
/** @var string $tokenValue */
/** @var string $statusText */
/** @var string $logText */
/** @var string $responseText */
?>
<div class="two-col">
  <div class="card">
    <div class="title">API 中转链路检测</div>
    <form method="post" action="?r=/trace">
      <div>
        <label>API URL</label>
        <input type="url" name="url" value="<?= htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8') ?>" required />
      </div>
      <input type="hidden" name="method" value="POST" />
      <div style="margin-top:12px">
        <label>API Key</label>
        <input type="text" name="token" value="<?= htmlspecialchars($tokenValue ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="sk-..." />
      </div>
      <div style="margin-top:12px">
        <label>模型</label>
        <input type="text" name="model" value="<?= htmlspecialchars($model, ENT_QUOTES, 'UTF-8') ?>" />
      </div>
      <div style="margin-top:16px">
        <button type="submit">发送请求</button>
      </div>
      <div class="section-title">请求状态</div>
      <pre><?= htmlspecialchars($statusText, ENT_QUOTES, 'UTF-8') ?></pre>
      <div class="section-title" style="margin-top:12px">请求日志</div>
      <pre><?= htmlspecialchars($logText, ENT_QUOTES, 'UTF-8') ?></pre>
    </form>
  </div>
  <div class="card">
    <div class="title">响应结果</div>
    <pre><?= htmlspecialchars($responseText, ENT_QUOTES, 'UTF-8') ?></pre>
  </div>
</div>


