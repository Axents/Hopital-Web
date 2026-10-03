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

if(isset($_POST['password'])){

    $nueva = $_POST['nueva_password'];
    $confirmar = $_POST['confirmar_password'];

    if($nueva !== $confirmar){

        $error = 'Las contraseñas nuevas no coinciden';

    }else{

        $passHash = password_hash($nueva, PASSWORD_DEFAULT);

        $updatePass = $conn->prepare("
            UPDATE usuarios
            SET password = ?
            WHERE id = ?
        ");

        $updatePass->bind_param(
            'si',
            $passHash,
            $_SESSION['usuario_id']
        );

        if($updatePass->execute()){
            $success = 'Tu contraseña se actualizó con éxito';
        }else{
            $error = 'No se pudo actualizar la contraseña';
        }
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

<div class="card" style="max-width: 800px; margin: 0 auto;">

<div class="card-header">
    <div>
        <h2>Información Personal</h2>
        <p>Modifica los campos necesarios para mantener tu expediente al día</p>
    </div>
</div>

<div class="card-body">

<?php if($success): ?>
<div class="alert alert-success">
    <?= $success ?>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger">
    <?= $error ?>
</div>
<?php endif; ?>

<form method="POST">

<div class="form-group" style="margin-bottom: 20px;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Nombre completo</label>
<input type="text"
       name="nombre"
       class="form-control"
       value="<?= htmlspecialchars($paciente['nombre']) ?>" required>
</div>

<div class="form-group" style="margin-bottom: 20px;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Correo electrónico</label>
<input type="email"
       name="email"
       class="form-control"
       value="<?= htmlspecialchars($paciente['email']) ?>" required>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px; margin-bottom: 20px;">

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Teléfono</label>
<input type="text"
       name="telefono"
       class="form-control"
       value="<?= htmlspecialchars($paciente['telefono'] ?? '') ?>"
       placeholder="Ej. 555-123-4567">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Dirección</label>
<input type="text"
       name="direccion"
       class="form-control"
       value="<?= htmlspecialchars($paciente['direccion'] ?? '') ?>"
       placeholder="Calle, número, ciudad">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Fecha de nacimiento</label>
<input type="date"
       name="fecha_nacimiento"
       class="form-control"
       value="<?= htmlspecialchars($paciente['fecha_nacimiento'] ?? '') ?>">
</div>

<div class="form-group">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Edad actual</label>
<input type="text"
       class="form-control"
       value="<?= $edad ? $edad . ' años' : 'No calculada' ?>"
       readonly style="background: #f1f5f9;">
</div>

<div class="form-group" style="grid-column: 1 / -1;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Tipo de sangre</label>
<input type="text"
       name="tipo_sangre"
       class="form-control"
       value="<?= htmlspecialchars($paciente['tipo_sangre'] ?? '') ?>"
       placeholder="Ej. O+">
</div>

<div class="form-group" style="grid-column:1 / span 2;">
<label style="display: block; font-weight: 700; margin-bottom: 8px; color: var(--dark);">Alergias o padecimientos relevantes</label>
<textarea name="alergias"
          class="form-control"
          rows="3"
          placeholder="Menciona si eres alérgico a algún medicamento o sustancia"><?= htmlspecialchars($paciente['alergias'] ?? '') ?></textarea>
</div>

</div>

<button type="submit"
        name="guardar"
        class="btn btn-primary" style="background: var(--emerald); border: none; padding: 12px 24px; border-radius: 50px; font-weight: 700; cursor: pointer;">
Guardar cambios
</button>

</form>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>