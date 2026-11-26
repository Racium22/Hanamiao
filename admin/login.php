<?php
declare(strict_types=1);

/**
 * FILE: admin/login.php
 * Trang đăng nhập khu vực Quản trị Hanamiao (độc lập, chuẩn form.md).
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

initSession();

if (isAdminLoggedIn()) {
    header('Location: index.php?page=dashboard');
    exit;
}

$username = '';
$error = '';
$fieldErrors = ['username' => '', 'password' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? null;
    $username = trim((string)($_POST['ten_dang_nhap'] ?? ''));
    $password = (string)($_POST['mat_khau'] ?? '');

    if (!verifyCsrfToken(is_string($csrfToken) ? $csrfToken : null)) {
        $error = 'Phiên biểu mẫu không hợp lệ. Vui lòng thử lại.';
    } else {
        if ($username === '') {
            $fieldErrors['username'] = 'Vui lòng nhập tên đăng nhập.';
        }
        if ($password === '') {
            $fieldErrors['password'] = 'Vui lòng nhập mật khẩu.';
        }

        if ($fieldErrors['username'] === '' && $fieldErrors['password'] === '') {
            try {
                $pdo = getConnection();
                $stmt = $pdo->prepare(
                    'SELECT ID_ND, TenND, TenDangNhap, MatKhau, Quyen, TrangThai
                     FROM NguoiDung
                     WHERE TenDangNhap = ?
                     LIMIT 1'
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($password, (string)$user['MatKhau'])) {
                    $error = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
                } elseif ((int)$user['TrangThai'] !== 1) {
                    $error = 'Tài khoản này đang bị khóa. Vui lòng liên hệ quản trị viên.';
                } else {
                    loginAdmin($user);
                    setFlash('success', 'Đăng nhập thành công. Xin chào ' . $user['TenND'] . '.');
                    header('Location: index.php?page=dashboard');
                    exit;
                }
            } catch (PDOException $exception) {
                error_log('[Hanamiao Admin Login] ' . $exception->getMessage());
                $error = 'Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị — Hanamiao</title>
    <link rel="stylesheet" href="../css/fonts.css">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="admin-auth-body">
    <main class="admin-auth-container">
        <div class="admin-auth-box">
            <a class="admin-brand admin-auth-brand" href="../index.php?page=home">
                <span class="admin-brand-mark" aria-hidden="true">H</span>
                <span class="admin-brand-name">Hanamiao Admin</span>
            </a>

            <h1 class="admin-auth-title">Đăng nhập quản trị</h1>
            <p class="admin-auth-desc">Dành cho quản trị viên và nhân viên vận hành tiệm hoa Hanamiao.</p>

            <?php if ($error !== ''): ?>
                <div class="admin-alert admin-alert-error" role="alert">
                    <?php echo h($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="post" class="admin-auth-form" novalidate>
                <?php echo csrfInput(); ?>

                <div class="admin-field">
                    <label for="ten_dang_nhap" class="admin-label">
                        Tên đăng nhập <span class="required-mark" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="ten_dang_nhap"
                        name="ten_dang_nhap"
                        type="text"
                        class="admin-input admin-input-lg<?php echo $fieldErrors['username'] !== '' ? ' is-error' : ''; ?>"
                        placeholder="Nhập tên đăng nhập"
                        value="<?php echo h($username); ?>"
                        autocomplete="username"
                        autofocus
                        required
                    >
                    <p class="admin-field-error"><?php echo h($fieldErrors['username']); ?></p>
                </div>

                <div class="admin-field">
                    <label for="mat_khau" class="admin-label">
                        Mật khẩu <span class="required-mark" aria-hidden="true">*</span>
                    </label>
                    <div class="admin-input-password">
                        <input
                            id="mat_khau"
                            name="mat_khau"
                            type="password"
                            class="admin-input admin-input-lg<?php echo $fieldErrors['password'] !== '' ? ' is-error' : ''; ?>"
                            placeholder="Nhập mật khẩu"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            type="button"
                            class="admin-password-toggle"
                            id="togglePasswordBtn"
                            aria-label="Hiện mật khẩu"
                        >
                            <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <p class="admin-field-error"><?php echo h($fieldErrors['password']); ?></p>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary admin-btn-lg admin-btn-block">
                    Đăng nhập
                </button>
            </form>

            <div class="admin-auth-footer">
                <a class="admin-link" href="../index.php?page=home">&larr; Quay lại cửa hàng Hanamiao</a>
            </div>
        </div>
    </main>

    <script>
        (function () {
            var btn = document.getElementById('togglePasswordBtn');
            var input = document.getElementById('mat_khau');
            if (!btn || !input) return;
            btn.addEventListener('click', function () {
                var isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                btn.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
            });
        })();
    </script>
</body>
</html>

