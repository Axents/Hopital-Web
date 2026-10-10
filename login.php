<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) redirect('pages/' . $_SESSION['rol'] . '/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email && $pass) {
        $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre']     = $user['nombre'];
            $_SESSION['rol']        = $user['rol'];
            redirect('pages/' . $user['rol'] . '/dashboard.php');
        } else {
            $error = 'El correo o la contraseña no coinciden, revísalos por favor.';
        }
    } else {
        $error = 'Faltan datos por llenar, completa los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso al Sistema — Clínica Uriangato</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --primary-navy: #0f172a;
      --accent-gold: #c5a059;
      --accent-gold-light: #fdf8f0;
      --slate-bg: #f8fafc;
      --text-main: #1e293b;
      --text-muted: #64748b;
      --border-color: #e2e8f0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      position: relative;
      overflow-x: hidden;
      padding: 30px 20px;
    }

    body::before {
      content: '';
      position: absolute; inset: 0;
      background: url('<?= BASE_URL ?>/assets/img/img-login.jpg') center/cover no-repeat;
      opacity: 0.15;
      mix-blend-mode: luminosity;
      z-index: 1;
    }

    .login-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 480px;
    }

    .login-wrapper {
      background: #ffffff;
      border-radius: 28px;
      padding: 48px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .brand-top {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--accent-gold-light);
      color: var(--accent-gold);
      padding: 8px 16px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin-bottom: 16px;
      border: 1px solid rgba(197, 160, 89, 0.2);
    }

    .brand-top h1 {
      font-size: 26px;
      font-weight: 800;
      color: var(--primary-navy);
      letter-spacing: -0.5px;
      margin-bottom: 6px;
    }

    .brand-top p {
      font-size: 14px;
      color: var(--text-muted);
    }

    .alert {
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 24px;
      background: #fef2f2;
      border: 1px solid #f87171;
      color: #991b1b;
      text-align: center;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: var(--primary-navy);
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 14px 18px;
      border: 1.5px solid var(--border-color);
      border-radius: 14px;
      font-size: 15px;
      font-family: inherit;
      color: var(--text-main);
      background: var(--slate-bg);
      transition: all 0.25s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--accent-gold);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.15);
    }

    .form-extras {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
      font-size: 13px;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      font-weight: 500;
      cursor: pointer;
    }

    .remember input {
      accent-color: var(--primary-navy);
      width: 16px;
      height: 16px;
    }

    .forgot {
      color: var(--accent-gold);
      font-weight: 700;
      text-decoration: none;
    }

    .forgot:hover {
      text-decoration: underline;
    }

    .btn-login {
      width: 100%;
      padding: 15px;
      background: var(--primary-navy);
      color: white;
      border: none;
      border-radius: 14px;
      font-size: 15px;
      font-weight: 800;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
      margin-bottom: 24px;
    }

    .btn-login:hover {
      background: var(--accent-gold);
      transform: translateY(-2px);
      box-shadow: 0 15px 25px rgba(197, 160, 89, 0.3);
    }

    .footer-links {
      text-align: center;
      font-size: 14px;
      color: var(--text-muted);
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
    }

    .footer-links a {
      color: var(--primary-navy);
      font-weight: 800;
      text-decoration: none;
    }

    .footer-links a:hover {
      color: var(--accent-gold);
      text-decoration: underline;
    }

    .back-home {
      display: block;
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      transition: color 0.2s;
    }

    .back-home:hover {
      color: #ffffff;
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-wrapper">
    <div class="brand-top">
      <div class="brand-badge">Clínica Uriangato</div>
      <h1>Iniciar Sesión</h1>
      <p>Introduce tus credenciales para continuar</p>
    </div>

    <?php if ($error): ?>
      <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" class="form-control"
               placeholder="tucorreo@ejemplo.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>

      <div class="form-extras">
        <label class="remember">
          <input type="checkbox" name="remember"> Recordar sesión
        </label>
        <a href="#" class="forgot">¿Olvidaste tu contraseña?</a>
      </div>

      <button type="submit" class="btn-login">Ingresar al sistema</button>
    </form>

    <div class="footer-links">
      ¿No tienes una cuenta? <a href="<?= BASE_URL ?>/register.php">Regístrate aquí</a>
    </div>
  </div>

  <a href="<?= BASE_URL ?>/index.php" class="back-home"> Regresar a la página principal</a>
</div>

</body>
</html>