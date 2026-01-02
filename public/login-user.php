<?php
/**
 * Login para usuarios/clientes
 * Separado del login de administrador
 */
require __DIR__ . "/../app/config/database.php";

// Si ya está logueado como usuario, redirigir al inicio
if (isset($_SESSION['user_id']) && isset($_SESSION['rol']) && $_SESSION['rol'] === 'cliente') {
    header("Location: index.php");
    exit;
}

// Si está logueado como admin, redirigir al admin
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$error = '';
$success = '';

// Manejar registro de nuevo usuario
if (isset($_POST['register'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Usuario y contraseña son obligatorios';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif (strlen($usuario) < 3) {
        $error = 'El usuario debe tener al menos 3 caracteres';
    } else {
        // Verificar si el usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        if ($stmt) {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'El usuario ya existe. Elige otro nombre de usuario.';
            } else {
                // Crear nuevo usuario
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, 'cliente')");
                if ($stmt) {
                    $stmt->bind_param("ss", $usuario, $hashed_password);
                    if ($stmt->execute()) {
                        $success = 'Usuario registrado correctamente. Inicia sesión ahora.';
                    } else {
                        $error = 'Error al registrar usuario';
                    }
                }
            }
            if ($stmt) $stmt->close();
        }
    }
}

// Manejar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Usuario y contraseña son obligatorios';
    } else {
        // Buscar usuario en la base de datos
        $stmt = $conn->prepare("SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ? AND rol = 'cliente'");
        if ($stmt) {
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verificar contraseña
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user'] = $user['usuario'];
                    $_SESSION['rol'] = 'cliente';
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
            $stmt->close();
        } else {
            $error = 'Error de conexión';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - LUNA</title>
    <link rel="stylesheet" href="../assets/css/shop.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e5e7eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 500px;
        }
        
        .login-left {
            background: linear-gradient(135deg, #f5f5f5 0%, #e5e7eb 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            color: #111;
            text-align: center;
        }
        
        .login-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: 56px;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: -2px;
        }
        
        .login-left p {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 20px;
        }
        
        .login-right {
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .login-tab {
            padding: 12px 24px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
        }
        
        .login-tab.active {
            color: #111;
        }
        
        .login-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #111;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #111;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: #111;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.25);
        }
        
        .error {
            background: #f3f4f6;
            color: #111;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #eeeeee;
            color: #111;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #111;
            text-decoration: none;
            font-size: 14px;
            margin-top: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }
            
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>Lun</h1>
            <p style="font-size: 12px; letter-spacing: 4px; text-transform: uppercase; margin-top: 0;">Fashion</p>
            <p>Bienvenido de vuelta</p>
            <p style="font-size: 14px; opacity: 0.8; margin-top: 10px;">Inicia sesión para continuar comprando</p>
        </div>
        
        <div class="login-right">
            <div class="login-tabs">
                <button class="login-tab active" onclick="showTab('login')">Iniciar Sesión</button>
                <button class="login-tab" onclick="showTab('register')">Registrarse</button>
            </div>
            
            <?php if ($error): ?>
                <div class="error">
                    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulario de Login -->
            <div id="login-tab" class="tab-content active">
                <form method="POST">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="form-group">
                        <label>Usuario o Email</label>
                        <input type="text" name="usuario" placeholder="Ingresa tu usuario o email" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" placeholder="Ingresa tu contraseña" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Iniciar Sesión</button>
                </form>
            </div>
            
            <!-- Formulario de Registro -->
            <div id="register-tab" class="tab-content">
                <form method="POST">
                    <input type="hidden" name="register" value="1">
                    
                    <div class="form-group">
                        <label>Usuario</label>
                        <input type="text" name="usuario" placeholder="Elige un nombre de usuario" required minlength="3">
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <input type="password" name="password_confirm" placeholder="Repite tu contraseña" required minlength="6">
                    </div>
                    
                    <button type="submit" class="btn-primary">Crear Cuenta</button>
                </form>
            </div>
            
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </div>
    
    <script>
        function showTab(tab) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            document.querySelectorAll('.login-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar el tab seleccionado
            document.getElementById(tab + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>

