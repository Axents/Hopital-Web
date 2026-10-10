<?php
$pageTitle = 'Configuración';
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

$pid = $paciente['id'];

$success = '';
$error = '';

if(isset($_POST['guardar'])){

    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $tipo_sangre = trim($_POST['tipo_sangre']);
    $alergias = trim($_POST['alergias']);

    $upUser = $conn->prepare("
        UPDATE usuarios
        SET nombre = ?, email = ?
        WHERE id = ?
    ");

    $upUser->bind_param(
        'ssi',
        $nombre,
        $email,
        $_SESSION['usuario_id']
    );

    $upPac = $conn->prepare("
        UPDATE pacientes
        SET telefono = ?, direccion = ?, fecha_nacimiento = ?, tipo_sangre = ?, alergias = ?
        WHERE id = ?
    ");

    $upPac->bind_param(
        'sssssi',
        $telefono,
        $direccion,
        $fecha_nacimiento,
        $tipo_sangre,
        $alergias,
        $pid
    );

    if($upUser->execute() && $upPac->execute()){

        $_SESSION['nombre'] = $nombre;

        $success = 'Tus datos se han actualizado correctamente';

        $paciente['nombre'] = $nombre;
        $paciente['email'] = $email;
        $paciente['telefono'] = $telefono;
        $paciente['direccion'] = $direccion;
        $paciente['fecha_nacimiento'] = $fecha_nacimiento;
        $paciente['tipo_sangre'] = $tipo_sangre;
        $paciente['alergias'] = $alergias;

        if(!empty($fecha_nacimiento)){
            $nacimiento = new DateTime($fecha_nacimiento);
            $hoy = new DateTime();
            $edad = $hoy->diff($nacimiento)->y;
        }

    }else{
        $error = 'Hubo un problema al actualizar tus datos. Inténtalo de nuevo.';
    }
}
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="main">
<div class="topbar">
    <div class="topbar-left">
        <h1>Configuración</h1>
        <p>Actualiza tu información personal y datos de contacto</p>
    </div>
    <div class="topbar-right">
        <div class="topbar-avatar">
            <?= strtoupper(substr($_SESSION['nombre'],0,2)) ?>
        </div>
    </div>
</div>

<div class="content">
<div class="card" style="max-width: 850px; margin: 0 auto; border-radius: 20px; box-shadow: 0 10px 30px rgba(15,23,42,0.05); border: 1px solid #e2e8f0;">

<div class="card-header" style="border-bottom: 1px solid #e2e8f0; padding: 24px;">
    <div>
        <h2 style="color: #0f172a; font-size: 20px; font-weight: 800;">Información Personal</h2>
        <p style="color: #64748b; font-size: 14px;">Modifica los campos necesarios para mantener tu expediente al día</p>
    </div>
</div>

<div class="card-body" style="padding: 30px;">

<?php if($success): ?>
<div class="alert alert-success" style="background: #fdf8f0; border: 1px solid #c5a059; color: #854d0e; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
    <?= $success ?>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger" style="background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 20px; font-weight: 600;">
    <?= $error ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-group" style="margin-bottom: 20px;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Nombre completo</label>
<input type="text" name="nombre" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;" value="<?= htmlspecialchars($paciente['nombre']) ?>" required>
</div>

<div class="form-group" style="margin-bottom: 20px;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Correo electrónico</label>
<input type="email" name="email" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;" value="<?= htmlspecialchars($paciente['email']) ?>" required>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px; margin-bottom: 20px;">

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Teléfono</label>
<input type="text" name="telefono" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;" value="<?= htmlspecialchars($paciente['telefono'] ?? '') ?>" placeholder="Ej. 555-123-4567">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Dirección</label>
<input type="text" name="direccion" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;" value="<?= htmlspecialchars($paciente['direccion'] ?? '') ?>" placeholder="Calle, número, ciudad">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Fecha de nacimiento</label>
<input type="date" name="fecha_nacimiento" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;" value="<?= htmlspecialchars($paciente['fecha_nacimiento'] ?? '') ?>">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Edad actual</label>
<input type="text" class="form-control" value="<?= $edad ? $edad . ' años' : 'No calculada' ?>" readonly style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f1f5f9; color: #64748b;">
</div>

<div class="form-group" style="grid-column: 1 / -1;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Tipo de sangre</label>
<input type="text" name="tipo_sangre" class="form-control" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc;" value="<?= htmlspecialchars($paciente['tipo_sangre'] ?? '') ?>" placeholder="Ej. O+">
</div>

<div class="form-group" style="grid-column: 1 / -1;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 13px;">Alergias o padecimientos relevantes</label>
<textarea name="alergias" class="form-control" rows="3" style="width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; background: #f8fafc; font-family: inherit;" placeholder="Menciona si eres alérgico a algún medicamento"><?= htmlspecialchars($paciente['alergias'] ?? '') ?></textarea>
</div>

</div>

<button type="submit" name="guardar" style="background: #0f172a; color: white; border: none; padding: 14px 28px; border-radius: 12px; font-weight: 800; cursor: pointer; transition: background 0.2s, transform 0.2s; box-shadow: 0 4px 12px rgba(15,23,42,0.15);">
Guardar cambios
</button>

</form>

</div>
</div>
</div>
</div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>