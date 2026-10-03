<?php
$pageTitle = 'Historial Clínico';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$rol = $_SESSION['rol'];
$pid = (int)($_GET['paciente_id'] ?? 0);

if ($rol === 'paciente') {
    $pacRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
    $pacRow->bind_param('i', $_SESSION['usuario_id']);
    $pacRow->execute();
    $pac = $pacRow->get_result()->fetch_assoc();
    $pid = $pac['id'] ?? 0;
}

if (!$pid) redirect('pages/' . $rol . '/dashboard.php');

$pacInfo = $conn->prepare("SELECT u.nombre, u.email, p.fecha_nacimiento, p.tipo_sangre, p.alergias FROM pacientes p JOIN usuarios u ON p.usuario_id = u.id WHERE p.id = ?");
$pacInfo->bind_param('i', $pid);
$pacInfo->execute();
$paciente = $pacInfo->get_result()->fetch_assoc();

$hist = $conn->prepare("
    SELECT h.*, u.nombre AS doctor, e.nombre AS especialidad, c.fecha AS fecha_cita, c.hora
    FROM historial h
    JOIN doctores d ON h.doctor_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    LEFT JOIN citas c ON h.cita_id = c.id
    WHERE h.paciente_id = ?
    ORDER BY h.fecha DESC
");
$hist->bind_param('i', $pid);
$hist->execute();
$historial = $hist->get_result();
$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Historial Clínico</h1>
        <p>Expediente médico de <?= htmlspecialchars($paciente['nombre'] ?? '') ?></p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>
    <div class="content">

      <div class="card" style="margin-bottom:24px;">
        <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px; padding: 24px;">
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Paciente</div>
            <div style="font-weight:700; color:var(--dark); font-size: 15px;"><?= htmlspecialchars($paciente['nombre'] ?? '—') ?></div>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Fecha de nacimiento</div>
            <div style="font-weight:600; color:var(--dark); font-size: 15px;"><?= $paciente['fecha_nacimiento'] ? date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) : 'No registrada' ?></div>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Tipo de sangre</div>
            <div style="font-weight:600; color:var(--dark); font-size: 15px;"><?= htmlspecialchars($paciente['tipo_sangre'] ?? 'No especificado') ?></div>
          </div>
          <div>
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:6px;">Alergias</div>
            <div style="font-weight:600; color:#e11d48; font-size: 15px;"><?= htmlspecialchars($paciente['alergias'] ?? 'Ninguna registrada') ?></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div><h2>Consultas anteriores</h2><p>Registro histórico de atenciones médicas</p></div>
          <?php if ($rol === 'doctor'): ?>
          <a href="<?= BASE_URL ?>/pages/historial/agregar.php?paciente_id=<?= $pid ?>" class="btn btn-primary btn-sm" style="background:var(--emerald); border-radius:50px; font-weight:700; padding: 8px 18px;">+ Agregar entrada</a>
          <?php endif; ?>
        </div>

        <?php $count = 0; while ($h = $historial->fetch_assoc()): $count++;
          $ini = strtoupper(substr($h['doctor'], 0, 2));
          $color = $colores[crc32($h['doctor']) % count($colores)];
        ?>
        <div style="padding:24px;border-bottom:1px solid #f1f5f9;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:14px;">
              <div class="table-avatar" style="background:<?= $color ?>; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 15px;"><?= $ini ?></div>
              <div>
                <div style="font-weight:800;font-size:15px; color:var(--dark);">Dr. <?= htmlspecialchars($h['doctor']) ?></div>
                <div style="font-size:12px;color:#64748b; font-weight:600;"><?= htmlspecialchars($h['especialidad'] ?? 'Medicina General') ?></div>
              </div>
            </div>
            <div style="font-size:13px;color:#64748b; font-weight:700; background: #f8fafc; padding: 6px 12px; border-radius: 20px;">
              <?= $h['fecha_cita'] ? date('d/m/Y', strtotime($h['fecha_cita'])) : date('d/m/Y', strtotime($h['fecha'])) ?>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px; background: #fafafa; padding: 16px; border-radius: 12px;">
            <?php if ($h['diagnostico']): ?>
            <div>
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:4px;">Diagnóstico</div>
              <div style="font-size:14px; color:var(--dark);"><?= htmlspecialchars($h['diagnostico']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['tratamiento']): ?>
            <div>
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:4px;">Tratamiento</div>
              <div style="font-size:14px; color:var(--dark);"><?= htmlspecialchars($h['tratamiento']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['medicamentos']): ?>
            <div>
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:4px;">Medicamentos</div>
              <div style="font-size:14px; color:var(--dark);"><?= htmlspecialchars($h['medicamentos']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['notas']): ?>
            <div>
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:4px;">Notas extras</div>
              <div style="font-size:14px; color:var(--dark);"><?= htmlspecialchars($h['notas']) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <div class="empty-state" style="padding: 50px; text-align: center;">
          <div class="empty-icon" style="margin-bottom: 12px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <p style="color:#64748b; font-weight:600;">No hay entradas registradas en el historial médico</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>