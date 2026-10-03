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

// Acceso rápido por rol
$quickEmail = '';
if (isset($_GET['rol'])) {
    $quickEmail = match($_GET['rol']) {
        'admin'    => 'admin@hospital.com',
        'doctor'   => 'doctor@hospital.com',
        'paciente' => 'paciente@hospital.com',
        default    => ''
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión — Hospital Palacio de la Salud</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --emerald: #059669;
      --emerald-dark: #047857;
      --emerald-light: #ecfdf5;
      --teal: #0d9488;
      --dark: #090d16;
      --gray-muted: #64748b;
      --border: #e2e8f0;
      --surface: #ffffff;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      background: var(--surface);
    }

    .left-panel {
      flex: 1;
      background: var(--dark);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 60px;
      position: relative;
      overflow: hidden;
      min-height: 100vh;
    }

    .left-panel::before {
      content: '';
      position: absolute; inset: 0;
      background: url('<?= BASE_URL ?>/assets/img/img-login.jpg') center/cover no-repeat;
      opacity: 0.35;
    }

    .left-panel::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(9,13,22,0.95), rgba(5,150,105,0.4));
    }

    .left-content { position: relative; z-index: 1; }

    .left-brand {
      display: flex; align-items: center; gap: 12px;
      color: white; font-size: 20px; font-weight: 800;
    }

    .left-hero {
      position: relative; z-index: 1;
      color: white;
      margin-bottom: 40px;
    }
    .left-hero h2 {
      font-size: clamp(32px, 3.5vw, 48px);
      font-weight: 800; line-height: 1.15;
      letter-spacing: -1px;
      margin-bottom: 16px;
    }
    .left-hero p {
      font-size: 16px;
      color: #94a3b8;
      line-height: 1.7;
      max-width: 420px;
    }

    .right-panel {
      width: 520px;
      flex-shrink: 0;
      background: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px;
      overflow-y: auto;
    }

    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 600; color: var(--gray-muted);
      margin-bottom: 36px;
      transition: color .2s;
      text-decoration: none;
    }
    .back-link:hover { color: var(--emerald); }

    .form-header { margin-bottom: 32px; }
    .form-header h1 {
      font-size: 30px; font-weight: 800;
      letter-spacing: -1px; color: var(--dark);
      margin-bottom: 8px;
    }
    .form-header p { font-size: 15px; color: var(--gray-muted); }

    .alert {
      padding: 14px 18px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 24px;
      background: #fff1f2;
      border: 1px solid #fecdd3;
      color: #be123c;
    }

    .form-group { margin-bottom: 20px; }
    .form-group label {
      display: block;
      font-size: 13px; font-weight: 700;
      color: var(--dark); margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 14px 18px;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      font-size: 14px;
      font-family: inherit;
      color: var(--dark);
      background: #fafafa;
      transition: all .2s ease;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--emerald);
      background: white;
      box-shadow: 0 0 0 4px rgba(5,150,105,.1);
    }

    .form-extras {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }
    .remember {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; font-weight: 500; color: var(--gray-muted); cursor: pointer;
    }
    .remember input { accent-color: var(--emerald); width: 16px; height: 16px; }
    .forgot { font-size: 13px; color: var(--emerald); font-weight: 700; text-decoration: none; }
    .forgot:hover { text-decoration: underline; }

    .btn-login {
      width: 100%;
      padding: 15px;
      background: var(--dark);
      color: white;
      border: none;
      border-radius: 50px;
      font-size: 15px;
      font-weight: 800;
      font-family: inherit;
      cursor: pointer;
      transition: all .3s ease;
      margin-bottom: 24px;
      box-shadow: 0 10px 20px rgba(9,13,22,.15);
    }
    .btn-login:hover {
      background: var(--emerald);
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(5,150,105,.25);
    }

    .divider {
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 24px;
      font-size: 13px; color: var(--gray-muted); font-weight: 500;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1;
      height: 1px; background: var(--border);
    }

    .quick-access { margin-bottom: 32px; }
    .quick-label {
      font-size: 12px; color: var(--gray-muted); font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.05em;
      text-align: center; margin-bottom: 12px;
    }
    .quick-btns {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 10px;
    }
    .quick-btn {
      padding: 10px;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      background: white;
      font-size: 13px; font-weight: 700;
      color: var(--dark);
      cursor: pointer;
      font-family: inherit;
      transition: all .2s ease;
      text-align: center;
      text-decoration: none;
      display: block;
    }
    .quick-btn:hover {
      border-color: var(--emerald);
      color: var(--emerald);
      background: var(--emerald-light);
    }
    .quick-btn.active {
      border-color: var(--emerald);
      background: var(--emerald);
      color: white;
    }

    .register-link {
      text-align: center;
      font-size: 14px; color: var(--gray-muted);
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }
    .register-link a { color: var(--emerald); font-weight: 800; text-decoration: none; }
    .register-link a:hover { text-decoration: underline; }

    @media (max-width: 900px) {
      .left-panel { display: none; }
      .right-panel { width: 100%; min-height: 100vh; padding: 30px; }
    }
  </style>
</head>
<body>

<div class="left-panel">
  <div class="left-content">
    <div class="left-brand">
      Hospital Palacio de la Salud
    </div>
  </div>

  <div class="left-hero left-content">
    <h2>Qué bueno verte por aquí</h2>
    <p>Entra a tu espacio personal para revisar tus citas agendadas, consultar tus recetas o checar cualquier detalle de tus consultas sin complicaciones.</p>
  </div>
  
  <div class="left-content" style="font-size: 13px; color: #64748b;">
    Hospital Palacio de la Salud.
  </div>
</div>

<div class="right-panel">
  <a href="<?= BASE_URL ?>/index.php" class="back-link">← Regresar a la página principal</a>

  <div class="form-header">
    <h1>Iniciar sesión</h1>
    <p>Escribe tus datos para entrar al sistema</p>
  </div>

  <?php if ($error): ?>
  <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label for="email">Tu correo electrónico</label>
      <input type="email" id="email" name="email" class="form-control"
             placeholder="tucorreo@ejemplo.com" required
             value="<?= htmlspecialchars($quickEmail ?: ($_POST['email'] ?? '')) ?>">
    </div>

    <div class="form-group">
      <label for="password">Tu contraseña</label>
      <input type="password" id="password" name="password" class="form-control"
             placeholder="••••••••" required>
    </div>

    <div class="form-extras">
      <label class="remember">
        <input type="checkbox" name="remember"> Recordar mis datos
      </label>
      <a href="#" class="forgot">¿No la recuerdas?</a>
    </div>

    <button type="submit" class="btn-login">Entrar a mi cuenta</button>
  </form>

  <div class="divider">O prueba directa</div>

  <div class="quick-access">
    <div class="quick-label">Entrar rápido como:</div>
    <div class="quick-btns">
      <a href="?rol=paciente" class="quick-btn <?= ($_GET['rol'] ?? '') === 'paciente' ? 'active' : '' ?>">Paciente</a>
      <a href="?rol=doctor"   class="quick-btn <?= ($_GET['rol'] ?? '') === 'doctor'   ? 'active' : '' ?>">Doctor</a>
      <a href="?rol=admin"    class="quick-btn <?= ($_GET['rol'] ?? '') === 'admin'    ? 'active' : '' ?>">Admin</a>
    </div>
  </div>

  <div class="register-link">
    ¿Todavía no tienes cuenta? <a href="<?= BASE_URL ?>/register.php">Crea una aquí</a>
  </div>
</div>

</body>
</html>