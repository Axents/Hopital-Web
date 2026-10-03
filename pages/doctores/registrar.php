<?php
$pageTitle = 'Registrar Doctor';
require_once __DIR__ . '/../../config/config.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/db.php';

$especialidades = $conn->query("SELECT * FROM especialidades ORDER BY nombre");
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $esp_id   = (int)$_POST['especialidad_id'];
    $telefono = trim($_POST['telefono'] ?? '');
    $cedula   = trim($_POST['cedula'] ?? '');

    if (!$nombre || !$email || !$pass) {
        $error = 'Por favor completa el nombre, correo y contraseña obligatorios.';
    } else {
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Este correo electrónico ya se encuentra registrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, 'doctor')");
            $ins->bind_param('sss', $nombre, $email, $hash);
            if ($ins->execute()) {
                $uid = $conn->insert_id;
                $esp = $esp_id ?: null;
                $doc = $conn->prepare("INSERT INTO doctores (usuario_id, especialidad_id, telefono, cedula) VALUES (?, ?, ?, ?)");
                $doc->bind_param('iiss', $uid, $esp, $telefono, $cedula);
                $doc->execute();
                $success = true;
            } else {
                $error = 'Hubo un error al registrar al doctor. Inténtalo de nuevo.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Registrar Nuevo Doctor</h1>
        <p>Añade un nuevo profesional médico al equipo del hospital</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>
    <div class="content">

      <?php if ($success): ?>
      <div class="card" style="max-width:500px; margin: 0 auto;">
        <div class="card-body" style="text-align:center;padding:48px 20px;">
          <div style="width:64px;height:64px;background:#ecfdf5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 style="font-size:22px;font-weight:800;margin-bottom:8px;color:var(--dark);">¡Doctor registrado con éxito!</h2>
          <p style="color:#64748b;font-size:14px;margin-bottom:28px;">El médico ha sido agregado correctamente a la plataforma.</p>
          <div style="display:flex;gap:12px;justify-content:center;">
            <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-primary" style="background:var(--emerald); border-radius:50px; font-weight:700; padding: 12px 24px;">Ver lista de doctores</a>
            <a href="<?= BASE_URL ?>/pages/doctores/registrar.php" class="btn btn-outline" style="border-radius:50px; font-weight:700; padding: 12px 24px;">Registrar otro</a>
          </div>
        </div>
      </div>

      <?php else: ?>
      <div class="card" style="max-width:640px; margin: 0 auto;">
        <div class="card-header">
          <div><h2>Datos del profesional</h2><p>Completa la información general del médico</p></div>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
              <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Nombre completo *</label>
              <input type="text" name="nombre" class="form-control"
                     placeholder="Ej. Roberto Gómez Mendoza" required
                     value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom: 20px;">
              <div class="form-group">
                <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Correo electrónico *</label>
                <input type="email" name="email" class="form-control"
                       placeholder="doctor@hospital.com" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Contraseña provisional *</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Mínimo 6 caracteres" required>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom: 20px;">
              <div class="form-group">
                <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Especialidad médica</label>
                <select name="especialidad_id" class="form-control">
                  <option value="">— Sin especialidad asignada —</option>
                  <?php while ($e = $especialidades->fetch_assoc()): ?>
                  <option value="<?= $e['id'] ?>" <?= ($_POST['especialidad_id'] ?? '') == $e['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['nombre']) ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Teléfono de contacto</label>
                <input type="text" name="telefono" class="form-control"
                       placeholder="Ej. 461 123 4567"
                       value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
              <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Cédula profesional</label>
              <input type="text" name="cedula" class="form-control"
                     placeholder="Número de cédula oficial"
                     value="<?= htmlspecialchars($_POST['cedula'] ?? '') ?>">
            </div>

            <div style="display:flex;gap:12px;">
              <button type="submit" class="btn btn-primary" style="background:var(--emerald); border:none; padding:12px 24px; border-radius:50px; font-weight:700; cursor:pointer; color:white;">Registrar doctor</button>
              <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-outline" style="border-radius:50px; font-weight:700; padding:12px 24px;">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>