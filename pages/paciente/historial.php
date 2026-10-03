<?php
$pageTitle = 'Historial Médico';
require_once __DIR__ . '/../../config/config.php';
requireRole('paciente');
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();
$pac = $pacienteRow->get_result()->fetch_assoc();

if(!$pac){
    die('No existe perfil de paciente');
}

$pid = $pac['id'];

$historial = $conn->prepare("
    SELECT 
        c.fecha,
        c.hora,
        c.estado,
        c.motivo,
        u.nombre AS doctor
    FROM citas c
    JOIN doctores d ON c.doctor_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    WHERE c.paciente_id = ?
    ORDER BY c.fecha DESC, c.hora DESC
");

$historial->bind_param('i', $pid);
$historial->execute();

$historialResult = $historial->get_result();

$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main">

<div class="topbar">
    <div class="topbar-left">
        <h1>Historial Médico</h1>
        <p>Registro completo de todas tus consultas pasadas y futuras</p>
    </div>

    <div class="topbar-right">
        <div class="topbar-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>
</div>

<div class="content">

<div class="card">

<div class="card-header">
    <div>
        <h2>Tus consultas registradas</h2>
        <p>Historial detallado de atención médica</p>
    </div>
</div>

<?php $count = 0; while($h = $historialResult->fetch_assoc()): $count++;

$ini = strtoupper(substr($h['doctor'],0,2));
$color = $colores[crc32($h['doctor']) % count($colores)];
$hora12 = date('h:i A', strtotime($h['hora']));

?>

<div class="cita-row">

<div class="cita-avatar" style="background:<?= $color ?>;">
    <?= $ini ?>
</div>

<div class="cita-info">
    <div class="nombre">Dr. <?= htmlspecialchars($h['doctor']) ?></div>
    <div class="sub"><?= htmlspecialchars($h['motivo'] ?? 'Consulta médica general') ?></div>
</div>

<div class="cita-meta">
    <span><?= date('d/m/Y', strtotime($h['fecha'])) ?></span>
    <span><?= $hora12 ?></span>
</div>

<span class="badge badge-info">
    <?= ucfirst($h['estado']) ?>
</span>

</div>

<?php endwhile; ?>

<?php if($count === 0): ?>

<div class="empty-state" style="padding: 40px; text-align: center;">
<div class="empty-icon" style="display:inline-flex; margin:0 auto 16px; justify-content:center; align-items:center;">
    <img src="<?= BASE_URL ?>/assets/img/svg/calendar.svg" width="40" height="40" style="filter:none; display:block;">
</div>
<p style="color: var(--muted); font-size: 15px;">Aún no cuentas con citas registradas en tu historial.</p>
</div>

<?php endif; ?>

</div>

</div>

</div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>