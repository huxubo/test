<?php
$title = 'DNS 提供商管理';
$editing = $editingProvider ?? null;
ob_start();
?>
<div class="card">
    <h2>提供商列表</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>名称</th>
            <th>类型</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($providers as $provider): ?>
            <tr>
                <td><?= e((string)$provider->id) ?></td>
                <td><?= e($provider->name) ?></td>
                <td><?= e($provider->provider_type) ?></td>
                <td><a class="button" href="<?= base_url('admin/providers') ?>?id=<?= $provider->id ?>">编辑</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2><?= $editing ? '编辑提供商' : '新增提供商' ?></h2>
    <form method="post" action="<?= base_url('admin/providers/save') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $editing?->id ?? 0 ?>">
        <div class="form-group">
            <label for="name">名称</label>
            <input type="text" id="name" name="name" value="<?= e($editing?->name ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="provider_type">类型</label>
            <select id="provider_type" name="provider_type" required>
                <?php
                $types = [
                    'powerdns' => 'PowerDNS',
                    'cloudflare' => 'Cloudflare',
                    'aliyun' => '阿里云 DNS',
                    'dnspod' => 'DNSPod',
                ];
                ?>
                <option value="">请选择</option>
                <?php foreach ($types as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= ($editing?->provider_type ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-tip" id="provider-params-tip">请选择提供商类型后填写对应参数。</div>
        <div class="form-group" data-field="api_account">
            <label for="api_account">账号信息</label>
            <input type="text" id="api_account" name="api_account" value="<?= e($editing?->api_account ?? '') ?>" placeholder="">
            <p class="help-text"></p>
        </div>
        <div class="form-group" data-field="api_key">
            <label for="api_key">API Key 或 Token</label>
            <input type="text" id="api_key" name="api_key" value="<?= e($editing?->api_key ?? '') ?>" placeholder="">
            <p class="help-text"></p>
        </div>
        <div class="form-group" data-field="api_secret">
            <label for="api_secret">API Secret</label>
            <input type="text" id="api_secret" name="api_secret" value="<?= e($editing?->api_secret ?? '') ?>" placeholder="">
            <p class="help-text"></p>
        </div>
        <div class="form-group" data-field="extra_params">
            <label for="extra_params">扩展参数</label>
            <textarea id="extra_params" name="extra_params" rows="4" placeholder=""><?= e($editing?->extra_params ?? '') ?></textarea>
            <p class="help-text"></p>
        </div>
        <button type="submit">保存</button>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('provider_type');
        if (!typeSelect) {
            return;
        }

        const tip = document.getElementById('provider-params-tip');
        const fields = {
            api_account: document.querySelector('[data-field="api_account"]'),
            api_key: document.querySelector('[data-field="api_key"]'),
            api_secret: document.querySelector('[data-field="api_secret"]'),
            extra_params: document.querySelector('[data-field="extra_params"]'),
        };

        const defaults = {
            api_account: {label: '账号信息', placeholder: '', help: '', required: false, visible: false},
            api_key: {label: 'API Key 或 Token', placeholder: '', help: '', required: false, visible: false},
            api_secret: {label: 'API Secret', placeholder: '', help: '', required: false, visible: false},
            extra_params: {label: '扩展参数', placeholder: '', help: '', required: false, visible: false},
        };

        const configs = {
            powerdns: {
                api_key: {visible: true, label: '管理 API Key', placeholder: 'PowerDNS X-API-Key', help: '用于请求 PowerDNS API 的 X-API-Key。', required: true},
                extra_params: {visible: true, placeholder: '{"base_url":"https://dns.example.com/api/v1","server_id":"localhost"}', help: '填写 base_url 与 server_id，需为合法 JSON。', required: true},
            },
            cloudflare: {
                api_key: {visible: true, label: 'API Token', placeholder: 'Cloudflare API Token', help: '需要具备 Zone.DNS 权限的 API Token。', required: true},
            },
            aliyun: {
                api_key: {visible: true, label: 'AccessKey ID', placeholder: '例如 LTAI5tXXXXXXXXX', help: '阿里云访问密钥 ID。', required: true},
                api_secret: {visible: true, label: 'AccessKey Secret', placeholder: '例如 BHi0XXXXXXXXX', help: '阿里云访问密钥 Secret。', required: true},
            },
            dnspod: {
                api_account: {visible: true, label: 'Token ID', placeholder: '例如 123456', help: 'DNSPod API Token ID。', required: true},
                api_key: {visible: true, label: 'Token Key', placeholder: '例如 abcdef123456', help: 'DNSPod API Token Key。', required: true},
            },
        };

        function applyConfig(type) {
            const overrides = configs[type] || {};
            let anyVisible = false;

            Object.entries(fields).forEach(([key, group]) => {
                if (!group) {
                    return;
                }

                const input = group.querySelector('input, textarea');
                const label = group.querySelector('label');
                const help = group.querySelector('.help-text');
                const state = Object.assign({}, defaults[key], overrides[key] || {});
                const shouldShow = Boolean(state.visible);

                group.style.display = shouldShow ? '' : 'none';

                if (input) {
                    input.disabled = !shouldShow;
                    input.required = shouldShow && Boolean(state.required);
                    input.placeholder = state.placeholder || '';
                }

                if (label) {
                    label.textContent = state.label || '';
                }

                if (help) {
                    help.textContent = state.help || '';
                    help.style.display = state.help ? 'block' : 'none';
                }

                if (shouldShow) {
                    anyVisible = true;
                }
            });

            if (tip) {
                tip.style.display = anyVisible ? 'none' : '';
            }
        }

        typeSelect.addEventListener('change', function () {
            applyConfig(typeSelect.value);
        });

        applyConfig(typeSelect.value);
    });
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
