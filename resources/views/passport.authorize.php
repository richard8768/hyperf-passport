<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $this->e($appname) ?> - Authorization</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --primary: #2563eb;
            /* 主色：蓝色 */
            --primary-dark: #1d4ed8;
            --danger: #dc2626;
            --text-main: #111827;
            --text-sub: #6b7280;
            --border-color: #e5e7eb;
            --bg-main: #f3f4f6;
            --bg-card: #ffffff;
            --radius-lg: 12px;
            --radius-md: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
        }

        .page-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 720px;
            background-color: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow:
                0 24px 48px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
            padding: 28px 32px 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
        }

        .app-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .app-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
        }

        .app-text-main {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-main);
        }

        .app-text-sub {
            font-size: 13px;
            color: var(--text-sub);
            margin-top: 2px;
        }

        .provider-info {
            text-align: right;
            font-size: 12px;
            color: var(--text-sub);
        }

        .provider-name {
            font-weight: 500;
            color: var(--text-main);
        }

        .card-body {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .section-desc {
            font-size: 13px;
            color: var(--text-sub);
        }

        .account-box {
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9fafb;
        }

        .account-main {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .avatar {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            color: white;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }

        .account-name {
            font-weight: 500;
            color: var(--text-main);
        }

        .account-email {
            font-size: 12px;
            color: var(--text-sub);
        }

        .account-switch {
            font-size: 12px;
            color: var(--primary);
            cursor: pointer;
        }

        .scope-list {
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 10px 12px;
            background-color: #f9fafb;
            font-size: 13px;
        }

        .scope-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 6px 0;
        }

        .scope-item+.scope-item {
            border-top: 1px dashed #e5e7eb;
            margin-top: 4px;
            padding-top: 10px;
        }

        .scope-label {
            font-weight: 500;
            color: var(--text-main);
        }

        .scope-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 6px;
        }

        .scope-tag.required {
            background-color: rgba(37, 99, 235, 0.06);
            color: var(--primary-dark);
        }

        .scope-tag.optional {
            background-color: rgba(107, 114, 128, 0.08);
            color: var(--text-sub);
        }

        .scope-desc {
            font-size: 12px;
            color: var(--text-sub);
            margin-top: 1px;
        }

        .scope-checkbox {
            margin-top: 2px;
        }

        .risk-box {
            border-radius: var(--radius-md);
            border: 1px solid rgba(220, 38, 38, 0.2);
            background-color: #fef2f2;
            padding: 10px 12px;
            font-size: 12px;
            color: #991b1b;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .risk-icon {
            font-size: 14px;
            line-height: 1.2;
        }

        .card-footer {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 4px;
        }

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            appearance: none;
            border: none;
            border-radius: 999px;
            padding: 9px 18px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.16s ease, box-shadow 0.16s ease, transform 0.08s ease;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 12px 20px rgba(37, 99, 235, 0.35);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(37, 99, 235, 0.4);
        }

        .btn-outline {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background-color: #f9fafb;
        }

        .hint-text {
            font-size: 11px;
            color: var(--text-sub);
        }

        .hint-text a {
            color: var(--primary);
            text-decoration: none;
        }

        .hint-text a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .card {
                padding: 20px 18px 16px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .provider-info {
                text-align: left;
            }

            .button-row {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="card">
            <div class="card-header">
                <div class="app-info">
                    <div>
                        <div class="app-text-main">"<?= $this->e($client->name) ?>" is requesting permission to access your account.</div>
                    </div>
                </div>
            </div>
            <?php
            if (count($scopes) > 0) {
            ?>
                <div class="card-body">

                    <!-- 授权范围 -->
                    <section>
                        <div class="section-title">The application is requesting the following permissions</div>
                        <div class="section-desc">
                            Please read the following permission instructions carefully.
                        </div>
                        <div class="scope-list">

                            <?php
                            foreach ($scopes as $scope) {
                            ?>

                                <div class="scope-item">

                                    <div>
                                        <?= $this->e($scope->description) ?>

                                    </div>
                                </div>
                            <?php
                            }
                            ?>


                        </div>
                    </section>

                </div>
            <?php
            }
            ?>

            <!-- 操作区 -->
            <div class="card-footer">
                <div class="button-row">
                    <form method="post" action="/oauth/authorize">

                        <input type="hidden" name="state" value="<?= $this->e($request->state) ?>">
                        <input type="hidden" name="client_id" value="<?= $this->e($client->id) ?>">
                        <input type="hidden" name="auth_token" value="<?= $this->e($authToken) ?>">
                        <button type="submit" class="btn btn-primary">Authorize</button>
                    </form>
                    <form method="post" action="/oauth/authorize">
                        <input type="hidden" name="method" value="delete">
                        <input type="hidden" name="state" value="<?= $this->e($request->state) ?>">
                        <input type="hidden" name="client_id" value="<?= $this->e($client->id) ?>">
                        <input type="hidden" name="auth_token" value="<?= $this->e($authToken) ?>">
                        <button type="button" class="btn btn-outline" id="btn-cancel">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('#btn-cancel');
            if (!btn) return;

            const form = btn.closest('form');
            if (!form) return;

            btn.disabled = true;

            try {
                const fd = new FormData(form);
                const body = new URLSearchParams();
                for (const [k, v] of fd.entries()) body.append(k, v);

                const res = await fetch(form.action, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body,
                    credentials: 'same-origin',
                    redirect: 'follow'
                });

                if (res.redirected) {
                    window.location.href = res.url;
                    return;
                }

                if (!res.ok) throw new Error('Request failed: ' + res.status);
                window.location.reload();
            } catch (err) {
                console.error(err);
                btn.disabled = false;
                alert('Failed to revoke authorization. Please try again.');
            }
        });
    </script>
</body>

</html>
