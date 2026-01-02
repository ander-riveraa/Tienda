<?php
// session_start();
require __DIR__ . "/../app/config/database.php";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded Admin Logic
    if ($email === 'admin@luna.com' && $password === 'admin123') {
        $_SESSION['rol'] = 'admin';
        header("Location: admin.php");
        exit;
    } else {
        $error = 'Credenciales inválidas.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Luna</title>
    <link rel="stylesheet" href="../assets/css/shop.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .login-container { display: flex; min-height: 100vh; }
        .login-left { flex: 1; background: linear-gradient(135deg, #f5f5f5 0%, #e5e7eb 100%); display: flex; align-items: center; justify-content: center; padding: 40px; }
        .login-logo { text-align: center; }
        .login-logo h1 { font-size: 72px; font-weight: 300; letter-spacing: -2px; color: #1f2937; margin-bottom: 8px; }
        .login-logo p { font-size: 12px; letter-spacing: 2px; color: #6b7280; text-transform: uppercase; }
        .login-right { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; background: #fff; }
        .login-form-wrapper { width: 100%; max-width: 400px; }
        .login-title { text-align: center; margin-bottom: 32px; }
        .login-title h2 { font-size: 18px; font-weight: 600; color: #1f2937; margin-bottom: 8px; letter-spacing: 0.5px; }
        .login-title p { font-size: 13px; color: #9ca3af; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: #111827; }
        .form-group input::placeholder { color: #d1d5db; }
        .form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 13px; }
        .form-options a { color: #111; text-decoration: none; }
        .form-options a:hover { text-decoration: underline; }
        .checkbox { display: flex; align-items: center; gap: 6px; }
        .checkbox input { width: 16px; height: 16px; cursor: pointer; }
        .error { color: #111; font-size: 13px; margin-bottom: 16px; background:#f3f4f6; padding:8px 12px; border-radius:6px; }
        .btn-login { width: 100%; padding: 12px; background: #111; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; letter-spacing: 1px; cursor: pointer; transition: transform 0.2s; }
        .btn-login:hover { transform: translateY(-2px); }
        .login-divider { text-align: center; margin: 24px 0; font-size: 13px; color: #d1d5db; }
        .login-divider::before, .login-divider::after { content: ''; display: inline-block; width: 40%; height: 1px; background: #e5e7eb; vertical-align: middle; }
        .login-divider::before { margin-right: 8px; }
        .login-divider::after { margin-left: 8px; }
        @media (max-width: 768px) { .login-container { flex-direction: column; } .login-left { padding: 20px; } .login-right { padding: 20px; } }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-left">
            <div class="login-logo">
                <h1>Lun</h1>
                <p>FASHION</p>
            </div>
        </div>
        <div class="login-right">
            <div class="login-form-wrapper">
                <div class="login-title">
                    <h2>ADMINISTRADOR</h2>
                    <p>Ingresa tus credenciales para continuar</p>
                </div>

                <form method="POST">
                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>USUARIO</label>
                        <input type="email" name="email" placeholder="Ingresa tu usuario" required value="admin@luna.com">
                    </div>

                    <div class="form-group">
                        <label>CLAVE</label>
                        <input type="password" name="password" placeholder="Ingresa tu contraseña" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox">
                            Recuérdame
                        </label>
                        <a href="#">¿Olvidaste tu clave?</a>
                    </div>

                    <button type="submit" class="btn-login">INGRESAR →</button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
