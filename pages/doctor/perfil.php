<?php
$pageTitle = 'Mi Perfil Profesional';
require_once __DIR__ . '/../../config/config.php';
requireRole('doctor');
require_once __DIR__ . '/../../includes/db.php';

$usuario_id = $_SESSION['usuario_id'];
$mensaje = '';
$error = '';

$checkDoctor = $conn->prepare("SELECT d.id FROM doctores d WHERE d.usuario_id = ?");
$checkDoctor->bind_param('i', $usuario_id);
$checkDoctor->execute();
$existeDoctor = $checkDoctor->get_result()->fetch_assoc();

if (!$existeDoctor) {
    $crearDoctor = $conn->prepare("INSERT INTO doctores (usuario_id, disponible) VALUES (?, 1)");
    $crearDoctor->bind_param('i', $usuario_id);
    $crearDoctor->execute();
}

$stmt = $conn->prepare("
    SELECT u.nombre, u.email,
           d.id, d.cedula, d.telefono, d.especialidad_id, d.disponible,
           e.nombre AS especialidad_nombre
    FROM usuarios u
    LEFT JOIN doctores d ON u.id = d.usuario_id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE u.id = ?
");
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
$doctor = $resultado->fetch_assoc();

$especialidades = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $especialidad_id = !empty($_POST['especialidad_id']) ? $_POST['especialidad_id'] : null;
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    if (!$nombre) {
        $error = 'Por favor ingresa tu nombre completo';
    } else {
        $updateUser = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $updateUser->bind_param('si', $nombre, $usuario_id);
        $updateUser->execute();
        
        $updateDoctor = $conn->prepare("UPDATE doctores SET cedula = ?, telefono = ?, especialidad_id = ?, disponible = ? WHERE usuario_id = ?");
        $updateDoctor->bind_param('ssiii', $cedula, $telefono, $especialidad_id, $disponible, $usuario_id);
        $updateDoctor->execute();
        
        $_SESSION['nombre'] = $nombre;
        header("Location: perfil.php?success=1");
        exit;
    }
}

$success = isset($_GET['success']);
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1 style="color: #0f172a; font-weight: 800;">Mi Perfil Profesional</h1>
        <p style="color: #64748b;">Configura tu información médica y datos de contacto</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" style="background: #0f172a; color: white; font-weight: 700;"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($success): ?>
        <div style="background: #fdf8f0; border: 1px solid #c5a059; color: #854d0e; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600;">Tus cambios se guardaron exitosamente</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div style="background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="card" style="max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px;">
        <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 20px;">Información Profesional</h2>
        
        <form method="POST">
          <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Nombre completo</label>
            <input type="text" name="nombre" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px;" 
                   value="<?= htmlspecialchars($doctor['nombre']) ?>" required>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
              <label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Cédula profesional</label>
              <input type="text" name="cedula" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px;" 
                     value="<?= htmlspecialchars($doctor['cedula'] ?? '') ?>" placeholder="Ej. 7483920">
            </div>
            <div>
              <label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Teléfono de contacto</label>
              <input type="tel" name="telefono" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px;" 
                     value="<?= htmlspecialchars($doctor['telefono'] ?? '') ?>" placeholder="Ej. 555-123-4567">
            </div>
          </div>

          <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Especialidad médica</label>
            <select name="especialidad_id" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: white;">
              <option value="">Selecciona tu especialidad</option>
              <?php while ($e = $especialidades->fetch_assoc()): ?>
                <option value="<?= $e['id'] ?>" <?= (($doctor['especialidad_id'] ?? null) == $e['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($e['nombre']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div style="margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; color: #0f172a; cursor: pointer;">
              <input type="checkbox" name="disponible" value="1" <?= ($doctor['disponible'] ?? 1) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
              Disponible actualmente para recibir citas
            </label>
          </div>

          <button type="submit" style="background: #0f172a; color: white; border: none; padding: 14px 28px; border-radius: 12px; font-weight: 800; cursor: pointer;">
            Guardar cambios
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>