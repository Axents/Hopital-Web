<?php
$pageTitle = 'Panel Médico';
require_once __DIR__ . '/../../config/config.php';
requireRole('doctor');
require_once __DIR__ . '/../../includes/db.php';

$docRow = $conn->prepare("SELECT d.id, e.nombre AS especialidad FROM doctores d LEFT JOIN especialidades e ON d.especialidad_id = e.id WHERE d.usuario_id = ?");
$docRow->bind_param('i', $_SESSION['usuario_id']);
$docRow->execute();
$doc = $docRow->get_result()->fetch_assoc();
$did = $doc['id'] ?? 0;
$especialidad = $doc['especialidad'] ?? 'Medicina General';

$pacientesHoy = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE doctor_id = ? AND fecha = CURDATE()");
$pacientesHoy->bind_param('i', $did); $pacientesHoy->execute();
$nHoy = $pacientesHoy->get_result()->fetch_assoc()['c'];

$totalPacientes = $conn->prepare("SELECT COUNT(DISTINCT paciente_id) c FROM citas WHERE doctor_id = ?");
$totalPacientes->bind_param('i', $did); $totalPacientes->execute();
$nTotal = $totalPacientes->get_result()->fetch_assoc()['c'];

$consultasMes = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE doctor_id = ? AND MONTH(fecha) = MONTH(CURDATE()) AND estado = 'completada'");
$consultasMes->bind_param('i', $did); $consultasMes->execute();
$nMes = $consultasMes->get_result()->fetch_assoc()['c'];

$pendientes = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE doctor_id = ? AND estado = 'pendiente'");
$pendientes->bind_param('i', $did); $pendientes->execute();
$nPend = $pendientes->get_result()->fetch_assoc()['c'];

$citasHoy = $conn->prepare("
    SELECT c.id, c.hora, c.estado, c.motivo,
           u.nombre AS paciente,
           pa.fecha_nacimiento
    FROM citas c
    JOIN pacientes pa ON c.paciente_id = pa.id
    JOIN usuarios u ON pa.usuario_id = u.id
    WHERE c.doctor_id = ? AND c.fecha = CURDATE()
    ORDER BY c.hora ASC
");
$citasHoy->bind_param('i', $did);
$citasHoy->execute();
$citasResult = $citasHoy->get_result();

$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">

    <div class="topbar">
      <div class="topbar-left">
        <h1>Mi Consultorio</h1>
        <p>Dr. <?= htmlspecialchars($_SESSION['nombre']) ?> &bull; <?= htmlspecialchars($especialidad) ?></p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">

      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-user.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nHoy ?></div>
            <div class="stat-label">Pacientes para Hoy</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/users-total.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nTotal ?></div>
            <div class="stat-label">Total de Pacientes</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-historial.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nMes ?></div>
            <div class="stat-label">Consultas este Mes</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/pin.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nPend ?></div>
            <div class="stat-label">Por Confirmar</div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div>
            <h2>Agenda de Hoy — <?= date('d/m/Y') ?></h2>
            <p>Tus consultas programadas para esta jornada</p>
          </div>
          <a href="<?= BASE_URL ?>/pages/doctor/citas.php" class="btn btn-outline btn-sm">Ver todas</a>
        </div>

        <?php $count = 0; while ($c = $citasResult->fetch_assoc()): $count++;
          $ini = strtoupper(substr($c['paciente'], 0, 2));
          $color = $colores[crc32($c['paciente']) % count($colores)];
          $edad = $c['fecha_nacimiento'] ? (date('Y') - date('Y', strtotime($c['fecha_nacimiento']))) . ' años' : '';
          $hora12 = date('h:i A', strtotime($c['hora']));
          $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success','cancelada'=>'badge-danger','completada'=>'badge-info'];
          $estadoLabel = ['pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Atendida'];
        ?>
        <div class="cita-row">
          <div class="cita-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
          <div class="cita-info">
            <div class="nombre"><?= htmlspecialchars($c['paciente']) ?></div>
            <div class="sub"><?= $edad ? $edad . ' &bull; ' : '' ?><?= htmlspecialchars(substr($c['motivo'] ?? 'Consulta general', 0, 40)) ?></div>
          </div>
          <div class="cita-meta">
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?= $hora12 ?>
            </span>
          </div>
          <span class="badge <?= $map[$c['estado']] ?? 'badge-gray' ?>"><?= $estadoLabel[$c['estado']] ?? ucfirst($c['estado']) ?></span>
          <a href="<?= BASE_URL ?>/pages/historial/agregar.php?cita_id=<?= $c['id'] ?>" class="btn btn-ghost btn-icon" title="Ver expediente">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </a>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <p>No tienes consultas agendadas para el día de hoy. ¡Disfruta tu jornada!</p>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<style>
  .stat-card-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
    background: #ecfdf5;
  }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>