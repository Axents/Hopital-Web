<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) redirect('pages/' . $_SESSION['rol'] . '/dashboard.php');

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if (!$nombre || !$email || !$pass) {
        $error = 'Por favor llena todos los espacios obligatorios.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden, revísalas por favor.';
    } elseif (strlen($pass) < 6) {
        $error = 'La contraseña es muy corta, usa al menos 6 caracteres.';
    } else {
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Este correo ya se encuentra registrado en el sistema.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'paciente')");
            $stmt->bind_param('sss', $nombre, $email, $hash);
            if ($stmt->execute()) {
                $uid = $conn->insert_id;
                $p = $conn->prepare("INSERT INTO pacientes (usuario_id) VALUES (?)");
                $p->bind_param('i', $uid);
                $p->execute();
                $success = true;
            } else {
                $error = 'Hubo un problema al registrarte. Inténtalo de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Regístrate — Clinica Uriangato</title>
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
      background: url('<?= BASE_URL ?>/assets/img/img-register.jpg') center/cover no-repeat;
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
      font-size: 16px; opacity: .8;
      line-height: 1.7; max-width: 420px; color: #94a3b8;
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
      transition: color .2s; text-decoration: none;
    }
    .back-link:hover { color: var(--emerald); }

    .form-header { margin-bottom: 32px; }
    .form-header h1 {
      font-size: 30px; font-weight: 800;
      letter-spacing: -1px; color: var(--dark); margin-bottom: 8px;
    }
    .form-header p { font-size: 15px; color: var(--gray-muted); }

    .alert {
      padding: 14px 18px; border-radius: 12px;
      font-size: 13px; font-weight: 600; margin-bottom: 24px;
    }
    .alert-danger  { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }

    .form-group { margin-bottom: 20px; }
    .form-group label {
      display: block; font-size: 13px; font-weight: 700;
      color: var(--dark); margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 14px 18px;
      border: 1.5px solid var(--border);
      border-radius: 12px;
      font-size: 14px; font-family: inherit;
      color: var(--dark); background: #fafafa;
      transition: all .2s ease;
    }
    .form-control:focus {
      outline: none; border-color: var(--emerald);
      background: white;
      box-shadow: 0 0 0 4px rgba(5,150,105,.1);
    }

    .strength-bar {
      height: 4px; background: var(--border);
      border-radius: 2px; overflow: hidden;
      margin-top: 8px;
    }
    .strength-fill {
      height: 100%; border-radius: 2px;
      transition: width .3s, background .3s;
      width: 0%;
    }
    .strength-text { font-size: 12px; color: var(--gray-muted); margin-top: 6px; font-weight: 600; }

    .btn-register {
      width: 100%; padding: 15px;
      background: var(--dark);
      color: white; border: none; border-radius: 50px;
      font-size: 15px; font-weight: 800; font-family: inherit;
      cursor: pointer; transition: all .3s ease;
      margin-bottom: 24px; margin-top: 10px;
      box-shadow: 0 10px 20px rgba(9,13,22,.15);
    }
    .btn-register:hover {
      background: var(--emerald);
      transform: translateY(-2px);
      box-shadow: 0 15px 30px rgba(5,150,105,.25);
    }

    .login-link {
      text-align: center; font-size: 14px; color: var(--gray-muted);
      padding-top: 24px; border-top: 1px solid var(--border);
    }
    .login-link a { color: var(--emerald); font-weight: 800; text-decoration: none; }
    .login-link a:hover { text-decoration: underline; }

    .success-state { text-align: center; padding: 30px 0; }
    .success-icon {
      width: 72px; height: 72px;
      background: var(--emerald-light); border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
    }
    .success-icon svg { width: 36px; height: 36px; stroke: var(--emerald); }
    .success-state h2 { font-size: 24px; font-weight: 800; margin-bottom: 8px; color: var(--dark); }
    .success-state p { font-size: 15px; color: var(--gray-muted); margin-bottom: 30px; line-height: 1.6; }
    .btn-goto {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      padding: 14px 28px; background: var(--dark); color: white;
      border-radius: 50px; font-size: 14px; font-weight: 800;
      text-decoration: none; transition: all .3s ease;
      box-shadow: 0 10px 20px rgba(9,13,22,.15);
    }
    .btn-goto:hover { background: var(--emerald); transform: translateY(-2px); }

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
      Clinica Uriangato
    </div>
  </div>
  <div class="left-hero left-content">
    <h2>Forma parte de nuestra gran familia</h2>
    <p>Crea tu cuenta para agendar citas, llevar el control de tus visitas y recibir la atención que mereces.</p>
  </div>
  <div class="left-content" style="font-size: 13px; color: #64748b;">
    Clinica Uriangato.
  </div>
</div>

<div class="right-panel">
  <a href="<?= BASE_URL ?>/login.php" class="back-link">Regresar al inicio de sesión</a>

  <?php if ($success): ?>
  <div class="success-state">
    <div class="success-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>
    <h2>Cuenta creada</h2>
    <p>Todo quedó listo. Ya puedes ingresar al sistema con tu correo y contraseña.</p>
    <a href="<?= BASE_URL ?>/login.php" class="btn-goto">Ir a iniciar sesión</a>
  </div>

  <?php else: ?>

  <div class="form-header">
    <h1>Crea tu cuenta</h1>
    <p>Regístrate de forma rápida como paciente</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label>Tu nombre completo</label>
      <input type="text" name="nombre" class="form-control"
             placeholder="Ej. Valeria Salinas" required
             value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Tu correo electrónico</label>
      <input type="email" name="email" class="form-control"
             placeholder="tucorreo@ejemplo.com" required
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label>Elige una contraseña</label>
      <input type="password" name="password" id="password" class="form-control"
             placeholder="Mínimo 6 caracteres" required>
      <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
      <div class="strength-text" id="strengthText"></div>
    </div>

    <div class="form-group">
      <label>Confirma tu contraseña</label>
      <input type="password" name="password2" class="form-control"
             placeholder="Repítela igual" required>
    </div>

    <button type="submit" class="btn-register">Registrarme ahora</button>
  </form>
  
  <div class="login-link">
    ¿Ya tienes una cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesión aquí</a>
  </div>

  <?php endif; ?>
</div>

<script>
const pwd = document.getElementById('password');
const fill = document.getElementById('strengthFill');
const text = document.getElementById('strengthText');

pwd.addEventListener('input', () => {
  const val = pwd.value;
  let score = 0;
  if (val.length >= 6) score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { w: '0%',   bg: 'transparent', t: '' },
    { w: '25%',  bg: '#ef4444', t: 'Muy débil' },
    { w: '50%',  bg: '#f97316', t: 'Débil' },
    { w: '75%',  bg: '#eab308', t: 'Regular' },
    { w: '90%',  bg: '#22c55e', t: 'Segura' },
    { w: '100%', bg: '#059669', t: 'Muy segura' },
  ];

  const l = levels[Math.min(score, 5)];
  fill.style.width = l.w;
  fill.style.background = l.bg;
  text.textContent = l.t;
  text.style.color = l.bg;
});
</script>

</body>
</html>