<?php
declare(strict_types=1);

/** @var string $title */
/** @var string $content */
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    :root{--bg:#f8fafc;--fg:#111827;--muted:#374151;--primary:#8b5cf6;--primary-600:#7c3aed;--card:#ffffff;--border:#e5e7eb;--codebg:#0b1021;--codefg:#ffffff}
    *{box-sizing:border-box}
    body{font-family:system-ui,-apple-system,Sego e UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;background:var(--bg);color:var(--fg);margin:0}
    header{display:flex;align-items:center;justify-content:center;padding:20px}
    header h1{margin:0;font-size:22px}
    .container{max-width:1100px;margin:0 auto;padding:16px}
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    label{display:block;margin-bottom:8px;color:var(--muted);font-size:14px}
    input[type="text"], input[type="url"], select{width:100%;padding:12px 12px;border:1px solid var(--border);border-radius:10px;background:#fff}
    button{width:100%;background:var(--primary);color:#fff;border:none;border-radius:10px;padding:12px 14px;cursor:pointer}
    button:hover{background:var(--primary-600)}
    .title{font-size:22px;font-weight:700;text-align:center;margin:6px 0 12px}
    .muted{color:var(--muted)}
    /* 强制日志与结果区域使用纯白字体 */
    pre.muted, #trace-logs, #img-url, #trace-result { color: var(--codefg) !important; }
    pre{background:var(--codebg);color:var(--codefg);padding:16px;border-radius:10px;overflow:auto;max-height:420px;white-space:pre-wrap;word-break:break-word;overflow-wrap:anywhere;line-height:1.5}
    .section-title{font-size:16px;font-weight:600;margin:10px 0}
    @media (max-width:960px){.two-col{grid-template-columns:1fr}}
  </style>
  </head>
<body>
  <header>
    <h1>API 测试工具</h1>
  </header>
  <div class="container">
    <?= $content ?>
  </div>
  <footer style="text-align:center;margin:24px 0;color:#6b7280;font-size:13px">
    © 清风阁工作室 · <a href="https://qfgapi.com/" target="_blank" rel="noopener" style="color:#6b7280;text-decoration:underline">https://qfgapi.com/</a>
  </footer>
</body>
</html>


