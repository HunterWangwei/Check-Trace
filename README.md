# Check-Trace
一个 PHP 网页版“中转链路检测”工具：向目标大模型 API 发送带图片的对话请求，谁来拉取图片就会在日志中出现；结合 User-Agent 与 IP 归属，判断是否为 Azure / OpenAI / 普通代理 / 逆向来源。
