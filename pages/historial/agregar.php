<?php
$pageTitle = 'Agregar al Historial';
require_once __DIR__ . '/../../config/config.php';
requireRole('doctor');
require_once __DIR__ . '/../../includes/db.php';

$docRow = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
$docRow->bind_param('i', $_SESSION['usuario_id']);
$docRow->execute();
$doc = $docRow->get_result()->fetch_assoc();
$did = $doc['id'] ?? 0;

$cita_id    = (int)($_GET['cita_id'] ?? $_POST['cita_id'] ?? 0);
$paciente_id = (int)($_GET['paciente_id'] ?? $_POST['paciente_id'] ?? 0);

if ($cita_id && !$paciente_id) {
    $citaRow = $conn->prepare("SELECT paciente_id FROM citas WHERE id = ? AND doctor_id = ?");
    $citaRow->bind_param('ii', $cita_id, $did);
    $citaRow->execute();
    $citaData = $citaRow->get_result()->fetch_assoc();
    $paciente_id = $citaData['paciente_id'] ?? 0;
}

if (!$paciente_id) redirect('pages/doctor/dashboard.php');

$pacInfo = $conn->prepare("SELECT u.nombre FROM pacientes p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$pacInfo->bind_param('i', $paciente_id);
$pacInfo->execute();
$paciente = $pacInfo->get_result()->fetch_assoc();

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnostico  = trim($_POST['diagnostico'] ?? '');
    $tratamiento  = trim($_POST['tratamiento'] ?? '');
    $medicamentos = trim($_POST['medicamentos'] ?? '');
    $notas        = trim($_POST['notas'] ?? '');
    $cita_id      = (int)$_POST['cita_id'];
    $paciente_id  = (int)$_POST['paciente_id'];

    if (!$diagnostico) {
        $error = 'Por favor escribe el diagnóstico del paciente.';
    } else {
        $cita_val = $cita_id ?: null;
        $ins = $conn->prepare("INSERT INTO historial (cita_id, paciente_id, doctor_id, diagnostico, tratamiento, medicamentos, notas) VALUES (?,?,?,?,?,?,?)");
        $ins->bind_param('iiissss', $cita_val, $paciente_id, $did, $diagnostico, $tratamiento, $medicamentos, $notas);
        if ($ins->execute()) {
            if ($cita_id) {
                $upd = $conn->prepare("UPDATE citas SET estado = 'completada' WHERE id = ?");
                $upd->bind_param('i', $cita_id);
                $upd->execute();
            }
            $success = true;
        } else {
            $error = 'Hubo un problema al guardar el registro. Inténtalo de nuevo.';
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
        <h1>Registrar en el Expediente</h1>
        <p>Paciente: <?= htmlspecialchars($paciente['nombre'] ?? '') ?></p>
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
          <h2 style="font-size:22px;font-weight:800;margin-bottom:8px;color:var(--dark);">¡Historial actualizado!</h2>
          <p style="color:#64748b;font-size:14px;margin-bottom:28px;">La consulta ha quedado registrada exitosamente en el expediente.</p>
          <div style="display:flex;gap:12px;justify-content:center;">
            <a href="<?= BASE_URL ?>/pages/doctor/dashboard.php" class="btn btn-primary" style="background:var(--emerald); border-radius:50px; font-weight:700; padding: 12px 24px;">Volver al panel</a>
            <a href="<?= BASE_URL ?>/pages/historial/ver.php?paciente_id=<?= $paciente_id ?>" class="btn btn-outline" style="border-radius:50px; font-weight:700; padding: 12px 24px;">Ver expediente</a>
          </div>
        </div>
      </div>

      <?php else: ?>
      <div class="card" style="max-width:680px; margin: 0 auto;">
        <div class="card-header">
          <div><h2>Nueva nota médica</h2><p>Completa la información clínica de la consulta actual</p></div>
        </div>
        <div class="card-body">
          <?php if ($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST">
            <input type="hidden" name="cita_id" value="<?= $cita_id ?>">
            <input type="hidden" name="paciente_id" value="<?= $paciente_id ?>">

            <div class="form-group" style="margin-bottom: 20px;">
              <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Diagnóstico clínico *</label>
              <textarea name="diagnostico" class="form-control" rows="3"
                        placeholder="Escribe el diagnóstico detallado del paciente..." required><?= htmlspecialchars($_POST['diagnostico'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Tratamiento indicado</label>
              <textarea name="tratamiento" class="form-control" rows="3"
                        placeholder="Describe el tratamiento o recomendaciones a seguir..."><?= htmlspecialchars($_POST['tratamiento'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
              <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Medicamentos recetados</label>
              <textarea name="medicamentos" class="form-control" rows="2"
                        placeholder="Indica nombre, dosis y frecuencia de los fármacos..."><?= htmlspecialchars($_POST['medicamentos'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
              <label style="font-weight:700; color:var(--dark); display:block; margin-bottom:8px;">Notas adicionales</label>
              <textarea name="notas" class="form-control" rows="2"
                        placeholder="Observaciones o indicaciones extras para el paciente..."><?= htmlspecialchars($_POST['notas'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:12px;">
              <button type="submit" class="btn btn-primary" style="background:var(--emerald); border:none; padding:12px 24px; border-radius:50px; font-weight:700; cursor:pointer; color:white;">Guardar entrada</button>
              <a href="<?= BASE_URL ?>/pages/doctor/dashboard.php" class="btn btn-outline" style="border-radius:50px; font-weight:700; padding:12px 24px;">Cancelar</a>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>