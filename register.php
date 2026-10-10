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
  <title>Registro de Paciente — Clínica Uriangato</title>
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
      padding: 40px 20px;
    }

    body::before {
      content: '';
      position: absolute; inset: 0;
      background: url('<?= BASE_URL ?>/assets/img/img-register.jpg') center/cover no-repeat;
      opacity: 0.15;
      mix-blend-mode: luminosity;
      z-index: 1;
    }

    .register-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 520px;
    }

    .register-wrapper {
      background: #ffffff;
      border-radius: 28px;
      padding: 48px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .brand-top {
      text-align: center;
      margin-bottom: 28px;
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
      margin-bottom: 14px;
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
      text-align: center;
    }
    .alert-danger  { background: #fef2f2; border: 1px solid #f87171; color: #991b1b; }
    .alert-success { background: #fdf8f0; border: 1px solid #c5a059; color: #854d0e; }

    .form-group {
      margin-bottom: 18px;
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
      padding: 13px 18px;
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

    .strength-bar {
      height: 4px;
      background: var(--border-color);
      border-radius: 2px;
      overflow: hidden;
      margin-top: 8px;
    }

    .strength-fill {
      height: 100%;
      border-radius: 2px;
      transition: width .3s, background .3s;
      width: 0%;
    }

    .strength-text {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 6px;
      font-weight: 600;
    }

    .btn-register {
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
      margin-top: 8px;
      margin-bottom: 24px;
    }

    .btn-register:hover {
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

    /* Estado de éxito */
    .success-state {
      text-align: center;
      padding: 20px 0;
    }
    .success-icon {
      width: 64px; height: 64px;
      background: var(--accent-gold-light);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      border: 1px solid rgba(197, 160, 89, 0.3);
    }
    .success-icon svg { width: 32px; height: 32px; stroke: var(--accent-gold); }
    .success-state h2 { font-size: 22px; font-weight: 800; margin-bottom: 8px; color: var(--primary-navy); }
    .success-state p { font-size: 14px; color: var(--text-muted); margin-bottom: 28px; line-height: 1.6; }
    
    .btn-goto {
      display: inline-flex; align-items: center; justify-content: center;
      width: 100%;
      padding: 14px; background: var(--primary-navy); color: white;
      border-radius: 14px; font-size: 15px; font-weight: 800;
      text-decoration: none; transition: all 0.3s ease;
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
    }
    .btn-goto:hover { background: var(--accent-gold); transform: translateY(-2px); }

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

<div class="register-container">
  <div class="register-wrapper">
    
    <?php if ($success): ?>
    <div class="success-state">
      <div class="success-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <h2>¡Cuenta creada con éxito!</h2>
      <p>Tu registro se ha completado correctamente. Ya puedes acceder al sistema con tus credenciales.</p>
      <a href="<?= BASE_URL ?>/login.php" class="btn-goto">Ir a iniciar sesión</a>
    </div>

    <?php else: ?>

    <div class="brand-top">
      <div class="brand-badge">Clínica Uriangato</div>
      <h1>Crea tu cuenta</h1>
      <p>Regístrate como paciente para agendar citas</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Nombre completo</label>
        <input type="text" name="nombre" class="form-control"
               placeholder="Ej. Valeria Salinas" required
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Correo electrónico</label>
        <input type="email" name="email" class="form-control"
               placeholder="tucorreo@ejemplo.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Contraseña</label>
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

      <button type="submit" class="btn-register">Completar Registro</button>
    </form>
    
    <div class="footer-links">
      ¿Ya tienes una cuenta? <a href="<?= BASE_URL ?>/login.php">Inicia sesión aquí</a>
    </div>

    <?php endif; ?>

  </div>

  <a href="<?= BASE_URL ?>/index.php" class="back-home"> Regresar a la página principal</a>
</div>

<script>
const pwd = document.getElementById('password');
if (pwd) {
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
      { w: '90%',  bg: '#2563eb', t: 'Segura' },
      { w: '100%', bg: '#c5a059', t: 'Muy segura' },
    ];

    const l = levels[Math.min(score, 5)];
    fill.style.width = l.w;
    fill.style.background = l.bg;
    text.textContent = l.t;
    text.style.color = l.bg;
  });
}
</script>

</body>
</html>