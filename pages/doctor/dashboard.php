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

$colores = ['#0f172a','#c5a059','#1e293b','#334155','#475569','#64748b'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1 style="color: #0f172a; font-weight: 800;">Mi Consultorio</h1>
        <p style="color: #64748b;">Dr. <?= htmlspecialchars($_SESSION['nombre']) ?> &bull; <?= htmlspecialchars($especialidad) ?></p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" style="background: #0f172a; color: white; font-weight: 700;"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 24px;">
        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0;">
          <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?= $nHoy ?></div>
          <div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;">Pacientes para Hoy</div>
        </div>
        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0;">
          <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?= $nTotal ?></div>
          <div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;">Total de Pacientes</div>
        </div>
        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0;">
          <div style="font-size: 26px; font-weight: 800; color: #0f172a;"><?= $nMes ?></div>
          <div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;">Consultas este Mes</div>
        </div>
        <div class="stat-card" style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0;">
          <div style="font-size: 26px; font-weight: 800; color: #c5a059;"><?= $nPend ?></div>
          <div style="font-size: 13px; font-weight: 600; color: #64748b; margin-top: 4px;">Por Confirmar</div>
        </div>
      </div>

      <div class="card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
          <div>
            <h2 style="color: #0f172a; font-size: 18px; font-weight: 800;">Agenda de Hoy — <?= date('d/m/Y') ?></h2>
            <p style="color: #64748b; font-size: 13px;">Tus consultas programadas para esta jornada</p>
          </div>
          <a href="<?= BASE_URL ?>/pages/doctor/citas.php" style="background: #0f172a; color: white; padding: 8px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; text-decoration: none;">Ver todas</a>
        </div>

        <?php $count = 0; while ($c = $citasResult->fetch_assoc()): $count++;
          $ini = strtoupper(substr($c['paciente'], 0, 2));
          $color = $colores[crc32($c['paciente']) % count($colores)];
          $edad = $c['fecha_nacimiento'] ? (date('Y') - date('Y', strtotime($c['fecha_nacimiento']))) . ' años' : '';
          $hora12 = date('h:i A', strtotime($c['hora']));
        ?>
        <div class="cita-row" style="display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #f1f5f9;">
          <div style="width: 42px; height: 42px; border-radius: 12px; background: <?= $color ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;"><?= $ini ?></div>
          <div style="flex: 1;">
            <div style="font-weight: 700; color: #0f172a; font-size: 14px;"><?= htmlspecialchars($c['paciente']) ?></div>
            <div style="color: #64748b; font-size: 13px;"><?= $edad ? $edad . ' &bull; ' : '' ?><?= htmlspecialchars(substr($c['motivo'] ?? 'Consulta general', 0, 40)) ?></div>
          </div>
          <div style="color: #64748b; font-size: 13px; font-weight: 600;"><?= $hora12 ?></div>
          <span style="padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a;"><?= ucfirst($c['estado']) ?></span>
          <a href="<?= BASE_URL ?>/pages/historial/agregar.php?cita_id=<?= $c['id'] ?>" style="padding: 8px 12px; background: #f1f5f9; border-radius: 10px; font-size: 12px; font-weight: 700; color: #0f172a; text-decoration: none;">Expediente</a>
        </div>
        <?php endwhile; ?>

        <?php if ($count === 0): ?>
        <div style="text-align: center; padding: 40px 0;">
          <p style="color: #64748b; font-size: 14px;">No tienes consultas agendadas para el día de hoy.</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>