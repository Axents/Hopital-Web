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

if (!$doctor) {
    $doctor = [
        'nombre' => $_SESSION['nombre'],
        'email' => '',
        'id' => 0,
        'cedula' => '',
        'telefono' => '',
        'especialidad_id' => null,
        'disponible' => 1,
        'especialidad_nombre' => null
    ];
} else {
    $doctor['nombre'] = $doctor['nombre'] ?? $_SESSION['nombre'];
    $doctor['email'] = $doctor['email'] ?? '';
    $doctor['id'] = $doctor['id'] ?? 0;
    $doctor['cedula'] = $doctor['cedula'] ?? '';
    $doctor['telefono'] = $doctor['telefono'] ?? '';
    $doctor['especialidad_id'] = $doctor['especialidad_id'] ?? null;
    $doctor['disponible'] = $doctor['disponible'] ?? 1;
    $doctor['especialidad_nombre'] = $doctor['especialidad_nombre'] ?? null;
}

$especialidades = $conn->query("SELECT id, nombre FROM especialidades ORDER BY nombre");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $cedula = trim($_POST['cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $especialidad_id = !empty($_POST['especialidad_id']) ? $_POST['especialidad_id'] : null;
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$nombre) {
        $error = 'Por favor ingresa tu nombre completo';
    } else {
        $updateUser = $conn->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $updateUser->bind_param('si', $nombre, $usuario_id);
        $updateUser->execute();
        
        $checkExist = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
        $checkExist->bind_param('i', $usuario_id);
        $checkExist->execute();
        $existe = $checkExist->get_result()->fetch_assoc();
        
        if ($existe) {
            $updateDoctor = $conn->prepare("UPDATE doctores SET cedula = ?, telefono = ?, especialidad_id = ?, disponible = ? WHERE usuario_id = ?");
            $updateDoctor->bind_param('ssiii', $cedula, $telefono, $especialidad_id, $disponible, $usuario_id);
            $updateDoctor->execute();
        } else {
            $insertDoctor = $conn->prepare("INSERT INTO doctores (usuario_id, cedula, telefono, especialidad_id, disponible) VALUES (?, ?, ?, ?, ?)");
            $insertDoctor->bind_param('issii', $usuario_id, $cedula, $telefono, $especialidad_id, $disponible);
            $insertDoctor->execute();
        }
        
        if ($new_password) {
            if (strlen($new_password) < 6) {
                $error = 'La contraseña nueva debe tener al menos 6 caracteres';
            } elseif ($new_password !== $confirm_password) {
                $error = 'Las contraseñas nuevas no coinciden';
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updatePass = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $updatePass->bind_param('si', $hash, $usuario_id);
                $updatePass->execute();
                $mensaje = 'Tus datos y contraseña se actualizaron correctamente';
            }
        } else {
            $mensaje = 'Tus datos profesionales se actualizaron correctamente';
        }
        
        if (!$error) {
            $_SESSION['nombre'] = $nombre;
            header("Location: perfil.php?success=1");
            exit;
        }
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
        <h1>Mi Perfil Profesional</h1>
        <p>Configura tu información médica y datos de contacto</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($success): ?>
        <div class="alert alert-success">Tus cambios se guardaron exitosamente</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="profile-container">
        <div class="profile-card">
          <div class="profile-card-header">
            <div class="profile-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div>
              <h2>Información Profesional</h2>
              <p>Datos personales visibles en el sistema</p>
            </div>
          </div>
          
          <form method="POST" class="profile-form">
            <div class="form-row">
              <div class="form-group full-width">
                <label>Nombre completo</label>
                <input type="text" name="nombre" class="form-control" 
                       value="<?= htmlspecialchars($doctor['nombre']) ?>" required>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label>Correo electrónico</label>
                <input type="email" class="form-control" 
                       value="<?= htmlspecialchars($doctor['email']) ?>" disabled>
                <span class="form-hint">El correo electrónico no se puede modificar por seguridad</span>
              </div>
            </div>

            <div class="form-row two-columns">
              <div class="form-group">
                <label>Cédula profesional</label>
                <input type="text" name="cedula" class="form-control" 
                       value="<?= htmlspecialchars($doctor['cedula']) ?>" 
                       placeholder="Ej. 7483920">
              </div>

              <div class="form-group">
                <label>Teléfono de contacto</label>
                <input type="tel" name="telefono" class="form-control" 
                       value="<?= htmlspecialchars($doctor['telefono']) ?>" 
                       placeholder="Ej. 555-123-4567">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label>Especialidad médica</label>
                <select name="especialidad_id" class="form-control">
                  <option value="">Selecciona tu especialidad</option>
                  <?php while ($e = $especialidades->fetch_assoc()): ?>
                    <option value="<?= $e['id'] ?>" 
                            <?= ($doctor['especialidad_id'] == $e['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($e['nombre']) ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label class="checkbox-container">
                  <input type="checkbox" name="disponible" value="1" 
                         <?= $doctor['disponible'] ? 'checked' : '' ?>>
                  <span class="checkmark"></span>
                  Disponible actualmente para recibir citas de pacientes
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                  <polyline points="17 21 17 13 7 13 7 21"/>
                  <polyline points="7 3 7 8 15 8"/>
                </svg>
                Guardar cambios
              </button>
            </div>
          </form>
        </div>

        <div class="profile-sidebar">
          <div class="profile-card">
            <div class="profile-card-header">
              <div class="profile-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </div>
              <div>
                <h2>Seguridad</h2>
                <p>Modifica tu contraseña de acceso</p>
              </div>
            </div>
            
            <form method="POST" class="profile-form">
              <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="new_password" class="form-control" 
                       placeholder="Mínimo 6 caracteres">
              </div>

              <div class="form-group">
                <label>Confirma la nueva contraseña</label>
                <input type="password" name="confirm_password" class="form-control" 
                       placeholder="Repítela igual">
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-secondary">
                  Actualizar contraseña
                </button>
              </div>
            </form>
          </div>

          <div class="stats-card">
            <div class="stats-card-header">
              <div class="profile-card-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
              </div>
              <div>
                <h2>Resumen de Actividad</h2>
                <p>Tus estadísticas generales</p>
              </div>
            </div>
            
            <?php
            $getDoctorId = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
            $getDoctorId->bind_param('i', $usuario_id);
            $getDoctorId->execute();
            $docData = $getDoctorId->get_result()->fetch_assoc();
            $currentDoctorId = $docData['id'] ?? 0;
            
            $countPacientes = $conn->prepare("SELECT COUNT(DISTINCT paciente_id) as total FROM citas WHERE doctor_id = ?");
            $countPacientes->bind_param('i', $currentDoctorId);
            $countPacientes->execute();
            $totalPacientes = $countPacientes->get_result()->fetch_assoc()['total'] ?? 0;
            
            $countCitas = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE doctor_id = ? AND estado = 'completada'");
            $countCitas->bind_param('i', $currentDoctorId);
            $countCitas->execute();
            $totalCitas = $countCitas->get_result()->fetch_assoc()['total'] ?? 0;
            
            $countPendientes = $conn->prepare("SELECT COUNT(*) as total FROM citas WHERE doctor_id = ? AND estado = 'pendiente'");
            $countPendientes->bind_param('i', $currentDoctorId);
            $countPendientes->execute();
            $totalPendientes = $countPendientes->get_result()->fetch_assoc()['total'] ?? 0;
            ?>
            
            <div class="stats-list">
              <div class="stat-item">
                <div class="stat-info">
                  <span class="stat-value"><?= $totalPacientes ?></span>
                  <span class="stat-label">Pacientes atendidos en total</span>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-info">
                  <span class="stat-value"><?= $totalCitas ?></span>
                  <span class="stat-label">Consultas concluidas con éxito</span>
                </div>
              </div>
              <div class="stat-item">
                <div class="stat-info">
                  <span class="stat-value"><?= $totalPendientes ?></span>
                  <span class="stat-label">Citas pendientes por revisar</span>
                </div>
              </div>
            </div>
            
            <div class="stats-footer">
              <div class="info-row">
                <span class="info-label">Especialidad actual:</span>
                <span class="info-value"><?= htmlspecialchars($doctor['especialidad_nombre'] ?? 'Sin asignar') ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.profile-container {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 28px;
  max-width: 1400px;
  margin: 0 auto;
}

.profile-card {
  background: white;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  margin-bottom: 28px;
  border: 1px solid var(--border);
}

.profile-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 24px 28px;
  background: #f8fafc;
  border-bottom: 1px solid var(--border);
}

.profile-card-icon {
  width: 48px;
  height: 48px;
  background: #ecfdf5;
  color: #059669;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-card-header h2 {
  font-size: 18px;
  font-weight: 800;
  color: var(--dark, #090d16);
  margin: 0 0 2px 0;
}

.profile-card-header p {
  font-size: 13px;
  color: var(--muted);
  margin: 0;
}

.profile-form { padding: 28px; }
.form-row { margin-bottom: 20px; }
.form-row.two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group.full-width { width: 100%; }

.form-group label {
  display: block; font-size: 13px; font-weight: 700;
  color: var(--dark, #090d16); margin-bottom: 8px;
}

.form-control {
  width: 100%; padding: 12px 16px;
  border: 1.5px solid var(--border);
  border-radius: 12px; font-size: 14px;
  font-family: inherit; color: var(--dark, #090d16);
  background: #fafafa; transition: all .2s ease;
}

.form-control:focus {
  outline: none; border-color: #059669;
  background: white; box-shadow: 0 0 0 4px rgba(5,150,105,.1);
}

.form-control:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
.form-hint { display: block; font-size: 12px; color: var(--muted); margin-top: 6px; }

.checkbox-container {
  display: flex; align-items: center; gap: 12px;
  cursor: pointer; font-size: 14px; font-weight: 600; color: var(--dark, #090d16);
}
.checkbox-container input { width: 18px; height: 18px; cursor: pointer; accent-color: #059669; }

.form-actions { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }

.btn-save {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  padding: 13px 26px; background: #059669; color: white;
  border-radius: 50px; font-size: 14px; font-weight: 800; cursor: pointer;
  border: none; transition: all .3s ease; box-shadow: 0 10px 20px rgba(5,150,105,.15);
}
.btn-save:hover { background: #047857; transform: translateY(-2px); }

.btn-secondary {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 12px 24px; background: #f1f5f9; color: var(--dark, #090d16);
  border-radius: 50px; font-size: 14px; font-weight: 700; cursor: pointer;
  border: none; transition: all .2s ease;
}
.btn-secondary:hover { background: #e2e8f0; }

.stats-card {
  background: var(--dark, #090d16);
  color: white; border-radius: 20px; overflow: hidden;
}
.stats-card-header {
  display: flex; align-items: center; gap: 14px;
  padding: 24px 28px; border-bottom: 1px solid rgba(255,255,255,.08);
}
.stats-card-header .profile-card-icon { background: rgba(255,255,255,.1); color: #34d399; }
.stats-card-header h2 { color: white; font-size: 18px; font-weight: 800; margin: 0 0 2px 0; }
.stats-card-header p { color: #94a3b8; font-size: 13px; margin: 0; }

.stats-list { padding: 20px 28px; }
.stat-item { padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,.06); }
.stat-item:last-child { border-bottom: none; }
.stat-value { font-size: 26px; font-weight: 800; color: #34d399; line-height: 1.2; }
.stat-label { font-size: 12px; color: #94a3b8; margin-top: 4px; font-weight: 600; }

.stats-footer { padding: 18px 28px; background: rgba(0,0,0,.2); border-top: 1px solid rgba(255,255,255,.06); }
.info-row { display: flex; justify-content: space-between; padding: 4px 0; }
.info-label { font-size: 13px; color: #94a3b8; }
.info-value { font-size: 13px; font-weight: 700; color: white; }

.alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; }
.alert-danger { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; }

@media (max-width: 1000px) {
  .profile-container { grid-template-columns: 1fr; }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>