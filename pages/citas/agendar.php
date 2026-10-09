<?php
$pageTitle = 'Agendar Cita';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();
$pac = $pacienteRow->get_result()->fetch_assoc();

if(!$pac){
    $crear = $conn->prepare("INSERT INTO pacientes (usuario_id) VALUES (?)");
    $crear->bind_param('i', $_SESSION['usuario_id']);
    $crear->execute();

    $pacienteRow->execute();
    $pac = $pacienteRow->get_result()->fetch_assoc();
}

$pid = $pac['id'] ?? null;

$doctores = $conn->query("
    SELECT d.id, u.nombre, e.nombre AS especialidad
    FROM doctores d
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE d.disponible = 1
    ORDER BY u.nombre
");

$especialidades = $conn->query("SELECT * FROM especialidades ORDER BY nombre");

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pid) {
    $doctor_id = (int)$_POST['doctor_id'];
    $fecha     = $_POST['fecha'] ?? '';
    $hora      = $_POST['hora'] ?? '';
    $motivo    = trim($_POST['motivo'] ?? '');

    if (!$doctor_id || !$fecha || !$hora) {
        $error = 'Por favor completa todos los campos obligatorios.';
    } elseif (strtotime($fecha) < strtotime('today')) {
        $error = 'La fecha elegida no puede ser en el pasado.';
    } else {
        $check = $conn->prepare("SELECT id FROM citas WHERE doctor_id=? AND fecha=? AND hora=? AND estado != 'cancelada'");
        $check->bind_param('iss', $doctor_id, $fecha, $hora);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Lo sentimos, este horario ya se encuentra ocupado. Elige otra hora.';
        } else {
            $ins = $conn->prepare("INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo) VALUES (?,?,?,?,?)");
            $ins->bind_param('iisss', $pid, $doctor_id, $fecha, $hora, $motivo);
            if ($ins->execute()) {
                $success = true;
            } else {
                $error = 'Hubo un problema al guardar la cita. Inténtalo de nuevo.';
            }
        }
    }
}
$preDoctor = (int)($_GET['doctor_id'] ?? 0);
$horas = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30',
          '12:00','12:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Agendar Cita</h1>
        <p>Programa tu próxima consulta médica en pocos pasos</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($success): ?>
      <div class="card" style="max-width:560px; margin: 0 auto;">
        <div class="card-body" style="text-align:center;padding:48px 20px;">
          <div style="width:64px;height:64px;background:#ecfdf5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <h2 style="font-size:22px;font-weight:800;margin-bottom:8px;color:var(--dark);">Cita agendada con éxito</h2>
          <p style="color:#64748b;font-size:15px;margin-bottom:28px;">Tu cita médica ha sido registrada correctamente en el sistema.</p>
          <div style="display:flex;gap:12px;justify-content:center;">
            <a href="<?= BASE_URL ?>/pages/citas/ver.php" class="btn btn-primary" style="background:var(--emerald); border-radius:50px; font-weight:700; padding: 12px 24px;">Ver mis citas</a>
            <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-outline" style="border-radius:50px; font-weight:700; padding: 12px 24px;">Agendar otra</a>
          </div>
        </div>
      </div>

      <?php else: ?>
      <div class="card-grid" style="grid-template-columns:1.2fr 0.8fr;">

        <div class="card">
          <div class="card-header">
            <div>
              <h2>Nueva cita médica</h2>
              <p>Selecciona el especialista y el horario de tu preferencia</p>
            </div>
          </div>
          <div class="card-body">
            <?php if (!$pid): ?>
            <div class="alert alert-danger">Tu cuenta actual no cuenta con un perfil de paciente activo. Por favor contacta al administrador.</div>
            <?php else: ?>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
              <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-weight: 700; color: var(--dark); display: block; margin-bottom: 8px;">Médico especialista </label>
                <select name="doctor_id" class="form-control" required>
                  <option value=""> Selecciona un especialista </option>
                  <?php
                  $doctores->data_seek(0);
                  while ($d = $doctores->fetch_assoc()):
                  ?>
                  <option value="<?= $d['id'] ?>" <?= $d['id'] == $preDoctor ? 'selected' : '' ?>>
                    Dr. <?= htmlspecialchars($d['nombre']) ?>
                    <?= $d['especialidad'] ? '(' . $d['especialidad'] . ')' : '' ?>
                  </option>
                  <?php endwhile; ?>
                </select>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom: 20px;">
                <div class="form-group">
                  <label style="font-weight: 700; color: var(--dark); display: block; margin-bottom: 8px;">Fecha </label>
                  <input type="date" name="fecha" class="form-control"
                         min="<?= date('Y-m-d') ?>" required
                         value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label style="font-weight: 700; color: var(--dark); display: block; margin-bottom: 8px;">Hora </label>
                  <select name="hora" class="form-control" required>
                    <option value=""> Selecciona hora </option>
                    <?php foreach ($horas as $h): ?>
                    <option value="<?= $h ?>" <?= ($_POST['hora'] ?? '') === $h ? 'selected' : '' ?>>
                      <?= date('h:i A', strtotime($h)) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>

              <div class="form-group" style="margin-bottom: 24px;">
                <label style="font-weight: 700; color: var(--dark); display: block; margin-bottom: 8px;">Motivo de la consulta</label>
                <textarea name="motivo" class="form-control" rows="3"
                          placeholder="Describe brevemente el motivo de tu visita..."><?= htmlspecialchars($_POST['motivo'] ?? '') ?></textarea>
              </div>

              <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn-primary" style="background:var(--emerald); border-radius:50px; font-weight:700; padding: 12px 24px; border:none; color:black; cursor:pointer;">Confirmar cita</button>
                <a href="<?= BASE_URL ?>/pages/paciente/dashboard.php" class="btn btn-outline" style="border-radius:50px; font-weight:700; padding: 12px 24px;">Cancelar</a>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div><h2>Médicos disponibles</h2><p>Especialistas listos para atenderte</p></div>
          </div>
          <div style="overflow-y:auto;max-height:420px; padding: 0 10px;">
            <?php
            $doctores->data_seek(0);
            $colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
            $i = 0;
            while ($d = $doctores->fetch_assoc()):
              $ini = strtoupper(substr($d['nombre'], 0, 2));
              $color = $colores[$i++ % count($colores)];
            ?>
            <div class="cita-row" style="align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9;">
              <div class="cita-avatar" style="background:<?= $color ?>; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 14px;"><?= $ini ?></div>
              <div class="cita-info" style="flex: 1; margin-left: 12px;">
                <div class="nombre" style="font-weight: 700; font-size: 14px; color: var(--dark);">Dr. <?= htmlspecialchars($d['nombre']) ?></div>
                <div class="sub" style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($d['especialidad'] ?? 'Medicina General') ?></div>
              </div>
              <span class="badge badge-success" style="background: #ecfdf5; color: #047857; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">Disponible</span>
            </div>
            <?php endwhile; ?>
          </div>
        </div>

      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>