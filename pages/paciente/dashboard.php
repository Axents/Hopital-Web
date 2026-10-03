<?php
$pageTitle = 'Mi Panel';
require_once __DIR__ . '/../../config/config.php';
requireRole('paciente');
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();
$pac = $pacienteRow->get_result()->fetch_assoc();
$pid = $pac['id'] ?? 0;

$totalCitas = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE paciente_id = ?");
$totalCitas->bind_param('i', $pid); $totalCitas->execute();
$nCitas = $totalCitas->get_result()->fetch_assoc()['c'];

$proximasCitas = $conn->prepare("SELECT COUNT(*) c FROM citas WHERE paciente_id = ? AND fecha >= CURDATE() AND estado != 'cancelada'");
$proximasCitas->bind_param('i', $pid); $proximasCitas->execute();
$nProximas = $proximasCitas->get_result()->fetch_assoc()['c'];

$totalHistorial = $conn->prepare("SELECT COUNT(*) c FROM historial WHERE paciente_id = ?");
$totalHistorial->bind_param('i', $pid); $totalHistorial->execute();
$nHistorial = $totalHistorial->get_result()->fetch_assoc()['c'];

$citas = $conn->prepare("
    SELECT c.id, c.fecha, c.hora, c.estado, c.motivo,
           u.nombre AS doctor, e.nombre AS especialidad
    FROM citas c
    JOIN doctores d ON c.doctor_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    WHERE c.paciente_id = ? AND c.fecha >= CURDATE()
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 5
");
$citas->bind_param('i', $pid);
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
        <h1>¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h1>
        <p>Bienvenido a tu panel de salud personal</p>
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
              <img src="<?= BASE_URL ?>/assets/img/svg/calendar.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nProximas ?></div>
            <div class="stat-label">Próximas Citas</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/consultas-totales.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nHistorial ?></div>
            <div class="stat-label">Registros Médicos</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-historial.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $nCitas ?></div>
            <div class="stat-label">Citas Históricas</div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-bottom:24px;">
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap; padding: 20px;">
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary" style="background: var(--emerald); border-radius: 50px; font-weight: 700; padding: 10px 20px;">
            Agendar Nueva Cita
          </a>
          <a href="<?= BASE_URL ?>/pages/paciente/historial.php" class="btn btn-outline" style="border-radius: 50px; font-weight: 700; padding: 10px 20px;">
            Ver Mi Historial
          </a>
          <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-outline" style="border-radius: 50px; font-weight: 700; padding: 10px 20px;">
            Conocer Especialistas
          </a>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div>
            <h2>Tus próximas citas</h2>
            <p>Consultas médicas programadas próximamente</p>
          </div>
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary btn-sm" style="background: var(--emerald); border-radius: 50px; font-weight: 700;">+ Agendar Cita</a>
        </div>

        <?php $count = 0; while ($c = $citasResult->fetch_assoc()): $count++;
          $ini = strtoupper(substr($c['doctor'], 0, 2));
          $color = $colores[crc32($c['doctor']) % count($colores)];
          $hora12 = date('h:i A', strtotime($c['hora']));
          $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success','cancelada'=>'badge-danger','completada'=>'badge-info'];
          $estadoLabel = ['pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Completada'];
        ?>
        <div class="cita-row">
          <div class="cita-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
          <div class="cita-info">
            <div class="nombre">Dr. <?= htmlspecialchars($c['doctor']) ?></div>
            <div class="sub"><?= htmlspecialchars($c['especialidad'] ?? 'Medicina General') ?></div>
          </div>
          <div class="cita-meta">
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?= date('d/m/Y', strtotime($c['fecha'])) ?>
            </span>
            <span>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <?= $hora12 ?>
            </span>
          </div>
          <span class="badge <?= $map[$c['estado']] ?? 'badge-gray' ?>"><?= $estadoLabel[$c['estado']] ?? ucfirst($c['estado']) ?></span>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <p>No tienes citas próximas en este momento. <a href="<?= BASE_URL ?>/pages/citas/agendar.php" style="color: var(--emerald); font-weight: 700;">Agenda una aquí</a></p>
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
    border: 1px solid #a7f3d0;
  }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>