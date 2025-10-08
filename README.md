# Check-Trace Web (PHP)

一款用于检测大模型 API 是否经过中转、转了几手的极简网页工具。原理是：向目标 API 发送包含图片的对话请求，任何中转/目标服务都会回源拉取该图片；系统记录每一次图片访问的 UA 与来源 IP，并结合 IP 归属信息输出检测到的中转节点。

## 特性
- 单页无刷新：表单提交后即时显示“检测中”状态与进度
- 实时日志：自动轮询 20 秒展示新发现的访问方（可按需调整）
- UA 规则：
  - 包含 `IPS` → Azure
  - 包含 `OpenAI` → OpenAI
  - 为空 → 未知（可能来自逆向）
  - 其他 → 普通代理
- IP 归属查询：基于公开接口（ip-api.com）展示组织、城市/地区、国家码与简写 DC
- 结果可读性：深色面板 + 白色等宽字体，超长 JSON 自动换行与滚动

```
在线演示：`https://check.898.hk/`

## 目录结构
```
  public/index.php               # 前端控制器（支持 ?r=/path 路由）
  src/Router.php                 # 极简路由
  src/Controllers/TraceController.php  # 表单页、AJAX 接口、图片回调
  src/Services/HttpClient.php           # cURL 客户端
  src/Services/TraceService.php         # 日志（临时文件存储）
  src/Services/UaClassifier.php         # UA 分类规则
  src/Services/IpInfoService.php        # IP 归属查询
  views/layout.php                # 布局与样式
  views/result.php                # 结果视图（再次提交表单）
  composer.json                   # PSR-4 自动加载（无 Composer 也可运行）
```

## 环境要求
- PHP 7.4+（推荐 8.0+）
- 启用 cURL 扩展
- 可对外访问的域名（用于回源拉取图片）

## 启动与部署
本地调试：
```bash
# 如使用 Composer：
composer install
# 启动 80 端口（Windows 需管理员；Linux/macOS 可用 sudo）
php -S 0.0.0.0:80 -t public
```
在线演示：`https://check.898.hk/`

生产部署（Nginx/Apache）：
- Web 根目录指向 `/public/`
- 无需配置重写，路由使用 `?r=/path`

## 使用方法
1. 打开 `http://your-domain/?r=/trace`
2. 填写：
   - API 域名：仅输入域名，如 `https://api.example.com`（程序会自动补全 `/v1/chat/completions`）
   - API Key：形如 `sk-...`
   - 模型：如 `gpt-4o`
3. 点击“开始检测”：
   - 左侧显示“临时图片 URL + HTTP 状态码”和“检测日志”
   - 右侧显示目标 API 的响应头与 JSON（自动格式化）

## 工作流程
1. 生成会话 ID 与临时图片 URL：`/?r=/img&id=<id>`
2. 发送带图片的对话请求至目标 API
3. 任何中转/目标服务回源拉取图片时，系统记录：时间、UA、X-Forwarded-For、IP
4. 聚合唯一 IP，并查询其组织/地理归属，输出“检测到中转节点: <组织> (<IP>) - 位置: <城市/地区, 国家码> [DC: <简码>]”

## 自定义
- 轮询时长与间隔：在 `views/layout.php` 引用的脚本中（`TraceController::form()` 内 `<script>`）调整 `setInterval` 与 `setTimeout`
- UA 规则：编辑 `src/Services/UaClassifier.php`
- 图片源：`TraceController::image()` 默认首次拉取 `https://picsum.photos/512` 缓存为真实图片，可替换为自有静态资源或固定 PNG/WebP
- 颜色与字体：`views/layout.php` 中 CSS 变量 `--codebg`/`--codefg`/`--muted` 及强制样式选择器 `#trace-logs/#trace-result`

## 常见问题
- 看不到公网 IP：请在反向代理处正确透传 `X-Forwarded-For`
- 仍收到 `invalid content type`：保证图片接口返回 `image/*`，必要时将图片固定为 PNG，并设置正确的 `Content-Type` 与 `Content-Length`
- 一直轮询：前端会在 20 秒后自动停止，可在脚本中提前在“检测到 OpenAI/Azure 时”停止

## 许可证
MIT 或与仓库根目录 `LICENSE` 一致的开源许可证。
