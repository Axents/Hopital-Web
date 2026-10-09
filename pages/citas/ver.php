<?php
$pageTitle = 'Gestión de Citas';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$rol = $_SESSION['rol'];

if ($rol === 'paciente') {
    $pacRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
    $pacRow->bind_param('i', $_SESSION['usuario_id']);
    $pacRow->execute();
    $pac = $pacRow->get_result()->fetch_assoc();
    $pid = $pac['id'] ?? 0;

    $citas = $conn->prepare("
        SELECT c.id, c.fecha, c.hora, c.estado, c.motivo,
               u.nombre AS doctor, e.nombre AS especialidad
        FROM citas c
        JOIN doctores d ON c.doctor_id = d.id
        JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN especialidades e ON d.especialidad_id = e.id
        WHERE c.paciente_id = ?
        ORDER BY c.fecha DESC, c.hora DESC
    ");
    $citas->bind_param('i', $pid);
} elseif ($rol === 'doctor') {
    $docRow = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
    $docRow->bind_param('i', $_SESSION['usuario_id']);
    $docRow->execute();
    $doc = $docRow->get_result()->fetch_assoc();
    $did = $doc['id'] ?? 0;

    $citas = $conn->prepare("
        SELECT c.id, c.fecha, c.hora, c.estado, c.motivo,
               u.nombre AS paciente, e.nombre AS especialidad
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN especialidades e ON e.id = (SELECT especialidad_id FROM doctores WHERE id = c.doctor_id)
        WHERE c.doctor_id = ?
        ORDER BY c.fecha DESC, c.hora DESC
    ");
    $citas->bind_param('i', $did);
} else {
    $citas = $conn->prepare("
        SELECT c.id, c.fecha, c.hora, c.estado, c.motivo,
               up.nombre AS paciente, ud.nombre AS doctor,
               e.nombre AS especialidad
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN usuarios up ON p.usuario_id = up.id
        JOIN doctores d ON c.doctor_id = d.id
        JOIN usuarios ud ON d.usuario_id = ud.id
        LEFT JOIN especialidades e ON d.especialidad_id = e.id
        ORDER BY c.fecha DESC, c.hora DESC
    ");
}
$citas->execute();
$citasResult = $citas->get_result();
$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1><?= $rol === 'admin' ? 'Control de Citas' : 'Mis Citas' ?></h1>
        <p><?= $rol === 'admin' ? 'Revisa y administra todas las citas del hospital' : 'Historial y seguimiento de tus consultas médicas' ?></p>
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot"></span>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">

      <?php if ($rol === 'paciente'): ?>
      <div style="margin-bottom:20px;">
        <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary" style="background:#DCDCDC; border-radius:50px; font-weight:700; padding: 12px 24px; color:black; ">+ Agendar Nueva Cita</a>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <div><h2>Listado general de citas</h2></div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <?php if ($rol === 'admin'): ?>
                <th>Paciente</th>
                <th>Médico</th>
                <?php elseif ($rol === 'paciente'): ?>
                <th>Médico</th>
                <?php else: ?>
                <th>Paciente</th>
                <?php endif; ?>
                <th>Especialidad</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Motivo</th>
                <th>Estatus</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php $count = 0; while ($c = $citasResult->fetch_assoc()): $count++;
              $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success','cancelada'=>'badge-danger','completada'=>'badge-info'];
              $estadoLabel = ['pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Atendida'];
              $persona = $rol === 'paciente' ? ($c['doctor'] ?? '') : ($c['paciente'] ?? ($c['doctor'] ?? ''));
              $ini = strtoupper(substr($persona, 0, 2));
              $color = $colores[crc32($persona) % count($colores)];
            ?>
              <tr>
                <?php if ($rol === 'admin'): ?>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $colores[crc32($c['paciente']) % count($colores)] ?>;"><?= strtoupper(substr($c['paciente'],0,2)) ?></div>
                    <div class="td-primary"><?= htmlspecialchars($c['paciente']) ?></div>
                  </div>
                </td>
                <td>Dr. <?= htmlspecialchars($c['doctor']) ?></td>
                <?php elseif ($rol === 'paciente'): ?>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
                    <div class="td-primary">Dr. <?= htmlspecialchars($c['doctor']) ?></div>
                  </div>
                </td>
                <?php else: ?>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
                    <div class="td-primary"><?= htmlspecialchars($c['paciente']) ?></div>
                  </div>
                </td>
                <?php endif; ?>
                <td><?= htmlspecialchars($c['especialidad'] ?? 'Medicina General') ?></td>
                <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                <td><?= date('h:i A', strtotime($c['hora'])) ?></td>
                <td><div class="td-muted"><?= htmlspecialchars(substr($c['motivo'] ?? 'Consulta general', 0, 30)) ?></div></td>
                <td><span class="badge <?= $map[$c['estado']] ?? 'badge-gray' ?>"><?= $estadoLabel[$c['estado']] ?? ucfirst($c['estado']) ?></span></td>
                <td>
                  <?php if ($c['estado'] === 'pendiente' && ($rol === 'paciente' || $rol === 'admin')): ?>
                  <a href="<?= BASE_URL ?>/pages/citas/cancelar.php?id=<?= $c['id'] ?>"
                     class="btn btn-danger btn-sm" style="background:#fff1f2; color:#be123c; border:1px solid #fecdd3; border-radius:8px; padding:6px 12px; font-weight:600;"
                     onclick="return confirm('¿Seguro que deseas cancelar esta cita?')">Cancelar</a>
                  <?php else: ?>
                  <span class="td-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endwhile; ?>
            <?php if ($count === 0): ?>
              <tr><td colspan="8">
                <div class="empty-state" style="padding: 40px; text-align: center;">
                  <div class="empty-icon" style="margin-bottom: 12px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  </div>
                  <p style="color: #64748b;">No hay citas registradas por el momento</p>
                </div>
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>