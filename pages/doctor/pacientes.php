<?php
$pageTitle = 'Mis Pacientes';
require_once __DIR__ . '/../../config/config.php';
requireRole('doctor');
require_once __DIR__ . '/../../includes/db.php';

$docRow = $conn->prepare("SELECT d.id FROM doctores d WHERE d.usuario_id = ?");
$docRow->bind_param('i', $_SESSION['usuario_id']);
$docRow->execute();
$doctor = $docRow->get_result()->fetch_assoc();
$doctor_id = $doctor['id'] ?? 0;

$search = $_GET['search'] ?? '';

$sql = "SELECT DISTINCT 
            p.id AS paciente_id,
            u.nombre,
            u.email,
            p.telefono,
            p.fecha_nacimiento,
            p.tipo_sangre,
            p.alergias,
            p.direccion,
            (SELECT COUNT(*) FROM citas WHERE paciente_id = p.id AND doctor_id = ?) AS total_citas,
            (SELECT MAX(fecha) FROM citas WHERE paciente_id = p.id AND doctor_id = ?) AS ultima_cita,
            (SELECT fecha FROM citas WHERE paciente_id = p.id AND doctor_id = ? AND estado != 'cancelada' ORDER BY fecha DESC LIMIT 1) AS ultima_consulta
        FROM pacientes p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN citas c ON c.paciente_id = p.id
        WHERE c.doctor_id = ?";

if ($search) {
    $sql .= " AND (u.nombre LIKE '%$search%' OR u.email LIKE '%$search%' OR p.telefono LIKE '%$search%')";
}
$sql .= " ORDER BY ultima_cita DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiii', $doctor_id, $doctor_id, $doctor_id, $doctor_id);
$stmt->execute();
$pacientes = $stmt->get_result();

$colores = ['#0f172a','#c5a059','#1e293b','#334155','#475569','#64748b'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1 style="color: #0f172a; font-weight: 800;">Mis Pacientes</h1>
        <p style="color: #64748b;">Personas que has atendido en consulta</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" style="background: #0f172a; color: white; font-weight: 700;"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <div class="card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px;">
        <h2 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 12px;">Buscar paciente</h2>
        <form method="GET" style="display: flex; gap: 12px;">
          <input type="text" name="search" class="form-control" style="flex: 1; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px;" 
                 placeholder="Busca por nombre, correo o teléfono..." 
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit" style="background: #0f172a; color: white; border: none; padding: 0 20px; border-radius: 12px; font-weight: 700; cursor: pointer;">Buscar</button>
          <?php if ($search): ?>
            <a href="pacientes.php" style="background: #f1f5f9; color: #0f172a; display: inline-flex; align-items: center; padding: 0 16px; border-radius: 12px; font-weight: 700; text-decoration: none;">Limpiar</a>
          <?php endif; ?>
        </form>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
        <?php while ($p = $pacientes->fetch_assoc()): 
          $color = $colores[crc32($p['nombre']) % count($colores)];
          $edad = $p['fecha_nacimiento'] ? (date('Y') - date('Y', strtotime($p['fecha_nacimiento']))) . ' años' : 'No registrada';
        ?>
          <div style="background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; gap: 16px; align-items: center;">
            <div style="width: 50px; height: 50px; border-radius: 50%; background: <?= $color ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; flex-shrink: 0;">
              <?= strtoupper(substr($p['nombre'], 0, 2)) ?>
            </div>
            <div style="flex: 1;">
              <h3 style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px;"><?= htmlspecialchars($p['nombre']) ?></h3>
              <p style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($p['email']) ?></p>
              <p style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Edad: <?= $edad ?> &bull; Sangre: <?= $p['tipo_sangre'] ?: 'N/D' ?></p>
            </div>
            <div>
              <a href="<?= BASE_URL ?>/pages/historial/ver.php?paciente_id=<?= $p['paciente_id'] ?>&doctor_id=<?= $doctor_id ?>" 
                 style="background: #0f172a; color: white; padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-block;">Expediente</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>