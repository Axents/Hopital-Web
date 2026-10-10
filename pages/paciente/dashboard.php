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

$colores = ['#0f172a','#c5a059','#1e293b','#334155','#475569','#64748b'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1 style="color: #0f172a; font-weight: 800;">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h1>
        <p style="color: #64748b;">Bienvenido a tu panel de salud personal</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" style="background: #0f172a; color: white; font-weight: 700;"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">

      <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
          <div class="stat-card-icon" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; background: #fdf8f0; border: 1px solid rgba(197,160,89,0.3); color: #c5a059;">
            <img src="<?= BASE_URL ?>/assets/img/svg/calendar.svg" width="22" height="22" alt="">
          </div>
          <div class="stat-value" style="font-size: 24px; font-weight: 800; color: #0f172a;"><?= $nProximas ?></div>
          <div class="stat-label" style="font-size: 13px; font-weight: 600; color: #64748b;">Próximas Citas</div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
          <div class="stat-card-icon" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; background: #fdf8f0; border: 1px solid rgba(197,160,89,0.3); color: #c5a059;">
            <img src="<?= BASE_URL ?>/assets/img/svg/consultas-totales.svg" width="22" height="22" alt="">
          </div>
          <div class="stat-value" style="font-size: 24px; font-weight: 800; color: #0f172a;"><?= $nHistorial ?></div>
          <div class="stat-label" style="font-size: 13px; font-weight: 600; color: #64748b;">Registros Médicos</div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
          <div class="stat-card-icon" style="width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; background: #fdf8f0; border: 1px solid rgba(197,160,89,0.3); color: #c5a059;">
            <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-historial.svg" width="22" height="22" alt="">
          </div>
          <div class="stat-value" style="font-size: 24px; font-weight: 800; color: #0f172a;"><?= $nCitas ?></div>
          <div class="stat-label" style="font-size: 13px; font-weight: 600; color: #64748b;">Citas Históricas</div>
        </div>
      </div>

      <div class="card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
        <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap; padding: 20px;">
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary" style="background: #0f172a; color: white; border-radius: 12px; font-weight: 700; padding: 12px 20px; text-decoration: none;">
            + Agendar Nueva Cita
          </a>
          <a href="<?= BASE_URL ?>/pages/paciente/historial.php" class="btn btn-outline" style="border: 1.5px solid #e2e8f0; color: #0f172a; border-radius: 12px; font-weight: 700; padding: 12px 20px; text-decoration: none;">
            Ver Mi Historial
          </a>
          <a href="<?= BASE_URL ?>/pages/doctores/listar.php" class="btn btn-outline" style="border: 1.5px solid #e2e8f0; color: #0f172a; border-radius: 12px; font-weight: 700; padding: 12px 20px; text-decoration: none;">
            Conocer Especialistas
          </a>
        </div>
      </div>

      <div class="card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div>
            <h2 style="color: #0f172a; font-size: 18px; font-weight: 800;">Tus próximas citas</h2>
            <p style="color: #64748b; font-size: 13px;">Consultas médicas programadas próximamente</p>
          </div>
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn btn-primary btn-sm" style="background: #c5a059; color: white; border-radius: 10px; font-weight: 700; padding: 8px 16px; font-size: 13px; text-decoration: none;">+ Agendar Cita</a>
        </div>

        <?php $count = 0; while ($c = $citasResult->fetch_assoc()): $count++;
          $ini = strtoupper(substr($c['doctor'], 0, 2));
          $color = $colores[crc32($c['doctor']) % count($colores)];
          $hora12 = date('h:i A', strtotime($c['hora']));
          $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success','cancelada'=>'badge-danger','completada'=>'badge-info'];
          $estadoLabel = ['pendiente'=>'Pendiente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','completada'=>'Completada'];
        ?>
        <div class="cita-row" style="display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #f1f5f9;">
          <div class="cita-avatar" style="width: 42px; height: 42px; border-radius: 12px; background: <?= $color ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;"><?= $ini ?></div>
          <div class="cita-info" style="flex: 1;">
            <div class="nombre" style="font-weight: 700; color: #0f172a; font-size: 14px;">Dr. <?= htmlspecialchars($c['doctor']) ?></div>
            <div class="sub" style="color: #64748b; font-size: 13px;"><?= htmlspecialchars($c['especialidad'] ?? 'Medicina General') ?></div>
          </div>
          <div class="cita-meta" style="display: flex; gap: 16px; color: #64748b; font-size: 13px; font-weight: 600;">
            <span><?= date('d/m/Y', strtotime($c['fecha'])) ?></span>
            <span><?= $hora12 ?></span>
          </div>
          <span class="badge" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a;"><?= $estadoLabel[$c['estado']] ?? ucfirst($c['estado']) ?></span>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <div class="empty-state" style="text-align: center; padding: 40px 0;">
          <p style="color: #64748b; font-size: 14px;">No tienes citas próximas en este momento. <a href="<?= BASE_URL ?>/pages/citas/agendar.php" style="color: #c5a059; font-weight: 700; text-decoration: none;">Agenda una aquí</a></p>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>