# 子域分发管理系统

本项目使用原生 PHP + MySQL 构建，用于在多个 DNS 平台（PowerDNS、Cloudflare、阿里云 DNS、DNSPod）之间统一管理主域，并为最终用户分发子域。系统支持邮箱验证注册、子域申请与审核、子域信息查询、账号间转移等功能。

## 功能特性

- 用户注册/登录（包含邮箱验证流程）
- 支持多家 DNS 服务商，统一封装 API：PowerDNS / Cloudflare / 阿里云 / DNSPod
- 用户可申请子域，系统根据配置自动或人工审核
- 子域仅生成 NS 类型记录，便于接入第三方权威 DNS
- 支持跨账号转移子域归属
- 管理后台可配置：
  - DNS 提供商参数
  - 主域列表
  - 子域审核模式、有效期
  - 用户初始子域配额

## 环境要求

- PHP >= 8.1，启用 PDO MySQL、cURL 扩展
- MySQL 5.7+ 或 MariaDB 10.3+
- Web 服务器（Apache/Nginx 等）指向 `public/` 目录

## 部署步骤

1. 克隆项目代码并安装依赖（本项目无第三方依赖）
2. 复制配置文件模板：
   ```bash
   cp config/config.example.php config/config.php
   ```
3. 编辑 `config/config.php`，填写数据库、邮件发送、站点基础信息
4. 创建数据库并导入结构：
   ```bash
   mysql -u root -p subdomain_manager < sql/schema.sql
   ```
5. 为 Web 服务器配置虚拟主机，根目录指向 `public/`
6. 访问站点开始使用。首次登录后台可在数据库中手动将某个用户的 `is_admin` 字段改为 1。

## DNS 提供商配置说明

- **PowerDNS**：在提供商的扩展参数中填写 `base_url` 与 `server_id`，例如 `{ "base_url": "https://dns.example.com/api/v1", "server_id": "localhost" }`
- **Cloudflare**：在主域中填写 `provider_reference` 为 Zone ID，提供商中设置 API Token
- **阿里云**：提供商配置 `api_key`（AccessKeyId）与 `api_secret`（AccessKeySecret）
- **DNSPod**：提供商配置 `api_account` 为 Token ID，`api_key` 为 Token Key

## 邮件发送

系统默认调用 PHP `mail()` 函数，并将邮件内容记录到 `storage/logs/emails.log` 方便调试。可在配置中改为使用 SMTP。

## 注意事项

- 所有表单均包含 CSRF 校验
- 代码中包含中文注释，便于二次开发
- 如需扩展更多 DNS 平台，可实现 `Services\DnsProviders\DnsProviderInterface`
