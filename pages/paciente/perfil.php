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
        <h1 style="color: #0f172a; font-weight: 800;">Mi Perfil</h1>
        <p style="color: #64748b;">Información general registrada en tu expediente</p>
    </div>
    <div class="topbar-right">
        <div class="topbar-avatar" style="background: #0f172a; color: white; font-weight: 700;">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>
</div>

<div class="content">
<div class="card" style="max-width: 850px; margin: 0 auto; border-radius: 20px; box-shadow: 0 10px 30px rgba(15,23,42,0.05); border: 1px solid #e2e8f0;">

<div class="card-header" style="border-bottom: 1px solid #e2e8f0; padding: 24px;">
    <div>
        <h2 style="color: #0f172a; font-size: 20px; font-weight: 800;">Datos del Paciente</h2>
        <p style="color: #64748b; font-size: 14px;">Información personal almacenada de forma segura</p>
    </div>
</div>

<div class="card-body" style="padding: 30px;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Nombre completo</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($paciente['nombre']) ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Correo electrónico</label>
<input type="email" class="form-control" value="<?= htmlspecialchars($paciente['email']) ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Teléfono</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($paciente['telefono'] ?? 'No registrado') ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Dirección</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($paciente['direccion'] ?? 'No registrada') ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Fecha de nacimiento</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($paciente['fecha_nacimiento'] ?? 'No registrada') ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Edad</label>
<input type="text" class="form-control" value="<?= $edad ? $edad . ' años' : 'No especificada' ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group" style="grid-column: 1 / -1;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Tipo de sangre</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($paciente['tipo_sangre'] ?? 'No registrado') ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b;">
</div>

<div class="form-group" style="grid-column: 1 / -1;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Alergias o padecimientos</label>
<textarea class="form-control" rows="3" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; color: #1e293b; resize: none; font-family: inherit;"><?= htmlspecialchars($paciente['alergias'] ?? 'No registradas') ?></textarea>
</div>

</div>
</div>

</div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>