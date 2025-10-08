<?php
declare(strict_types=1);

ob_start();
?>
<div class="two-col">
  <div class="card">
    <div class="title">API 测试工具</div>
    <form method="post" action="?r=/apitest">
      <div>
        <label>API URL</label>
        <input type="url" name="url" placeholder="https://qfqapi.com/v1/chat/completions" required />
      </div>
      <input type="hidden" name="method" value="POST" />
      <div style="margin-top:12px">
        <label>API Key</label>
        <input type="text" name="token" placeholder="sk-..." />
      </div>
      <div style="margin-top:12px">
        <label>模型</label>
        <input type="text" name="model" value="gpt-3.5-turbo" placeholder="如 gpt-3.5-turbo / gemini-2.0-flash 等" />
      </div>
      <div style="margin-top:16px">
        <button type="submit">发送请求</button>
      </div>
      <div class="section-title">请求状态</div>
      <div class="muted">提交后将在右侧显示响应与日志</div>
      <div class="section-title" style="margin-top:12px">请求日志</div>
      <pre class="muted">等待发送...</pre>
    </form>
  </div>
  <div class="card">
    <div class="title">响应结果</div>
    <pre class="muted">等待请求...</pre>
  </div>
</div>

<?php $content = ob_get_clean();
$title = 'API 测试工具';
include __DIR__ . '/layout.php';


