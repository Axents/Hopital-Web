<?php
$pageTitle = 'Mi Perfil';
require_once __DIR__ . '/../../config/config.php';
requireRole('paciente');
require_once __DIR__ . '/../../includes/db.php';

$pacienteRow = $conn->prepare("
    SELECT 
        p.*,
        u.nombre,
        u.email
    FROM pacientes p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.usuario_id = ?
");

$pacienteRow->bind_param('i', $_SESSION['usuario_id']);
$pacienteRow->execute();

$paciente = $pacienteRow->get_result()->fetch_assoc();

if(!$paciente){
    die('No existe perfil de paciente');
}

$edad = '';

if(!empty($paciente['fecha_nacimiento'])){
    $nacimiento = new DateTime($paciente['fecha_nacimiento']);
    $hoy = new DateTime();
    $edad = $hoy->diff($nacimiento)->y;
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main">

<div class="topbar">

    <div class="topbar-left">
        <h1>Mi Perfil</h1>
        <p>Información general registrada en tu expediente</p>
    </div>

    <div class="topbar-right">
        <div class="topbar-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>

</div>

<div class="content">

<div class="card" style="max-width: 800px; margin: 0 auto;">

<div class="card-header">
    <div>
        <h2>Datos del Paciente</h2>
        <p>Información personal almacenada de forma segura</p>
    </div>
</div>

<div class="card-body">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Nombre completo</label>
<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['nombre']) ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Correo electrónico</label>
<input type="email"
       class="form-control"
       value="<?= htmlspecialchars($paciente['email']) ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Teléfono</label>
<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['telefono'] ?? 'No registrado') ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Dirección</label>
<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['direccion'] ?? 'No registrada') ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Fecha de nacimiento</label>
<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['fecha_nacimiento'] ?? 'No registrada') ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Edad</label>
<input type="text"
       class="form-control"
       value="<?= $edad ? $edad . ' años' : 'No especificada' ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group" style="grid-column: 1 / -1;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Tipo de sangre</label>
<input type="text"
       class="form-control"
       value="<?= htmlspecialchars($paciente['tipo_sangre'] ?? 'No registrado') ?>"
       readonly style="background: #f8fafc;">
</div>

<div class="form-group" style="grid-column:1 / span 2;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Alergias o padecimientos</label>
<textarea class="form-control"
          rows="3"
          readonly style="background: #f8fafc; resize: none;"><?= htmlspecialchars($paciente['alergias'] ?? 'No registradas') ?></textarea>
</div>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>