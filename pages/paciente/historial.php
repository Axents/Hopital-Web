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

$colores = ['#0f172a','#c5a059','#1e293b','#334155','#475569','#64748b'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main">
<div class="topbar">
    <div class="topbar-left">
        <h1 style="color: #0f172a; font-weight: 800;">Historial Médico</h1>
        <p style="color: #64748b;">Registro completo de todas tus consultas pasadas y futuras</p>
    </div>
    <div class="topbar-right">
        <div class="topbar-avatar" style="background: #0f172a; color: white; font-weight: 700;">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>
</div>

<div class="content">
<div class="card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px;">

<div class="card-header" style="margin-bottom: 20px;">
    <div>
        <h2 style="color: #0f172a; font-size: 18px; font-weight: 800;">Tus consultas registradas</h2>
        <p style="color: #64748b; font-size: 13px;">Historial detallado de atención médica</p>
    </div>
</div>

<?php $count = 0; while($h = $historialResult->fetch_assoc()): $count++;

$ini = strtoupper(substr($h['doctor'],0,2));
$color = $colores[crc32($h['doctor']) % count($colores)];
$hora12 = date('h:i A', strtotime($h['hora']));
?>

<div class="cita-row" style="display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid #f1f5f9;">

<div class="cita-avatar" style="width: 42px; height: 42px; border-radius: 12px; background: <?= $color ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
    <?= $ini ?>
</div>

<div class="cita-info" style="flex: 1;">
    <div class="nombre" style="font-weight: 700; color: #0f172a; font-size: 14px;">Dr. <?= htmlspecialchars($h['doctor']) ?></div>
    <div class="sub" style="color: #64748b; font-size: 13px;"><?= htmlspecialchars($h['motivo'] ?? 'Consulta médica general') ?></div>
</div>

<div class="cita-meta" style="display: flex; gap: 16px; color: #64748b; font-size: 13px; font-weight: 600;">
    <span><?= date('d/m/Y', strtotime($h['fecha'])) ?></span>
    <span><?= $hora12 ?></span>
</div>

<span class="badge" style="padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; background: #fdf8f0; border: 1px solid rgba(197,160,89,0.3); color: #c5a059;">
    <?= ucfirst($h['estado']) ?>
</span>

</div>

<?php endwhile; ?>

<?php if($count === 0): ?>
<div class="empty-state" style="padding: 40px; text-align: center;">
<p style="color: #64748b; font-size: 14px;">Aún no cuentas con citas registradas en tu historial.</p>
</div>
<?php endif; ?>

</div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>