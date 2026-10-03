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

$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Mis Pacientes</h1>
        <p>Personas que has atendido en consulta</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
          <h2>Buscar paciente</h2>
        </div>
        <form method="GET" style="display: flex; gap: 12px; padding: 4px;">
          <input type="text" name="search" class="form-control" style="flex: 1;" 
                 placeholder="Busca por nombre, correo o teléfono..." 
                 value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn btn-primary">Buscar</button>
          <?php if ($search): ?>
            <a href="pacientes.php" class="btn btn-ghost">Mostrar todos</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="stats-grid" style="margin-bottom: 24px; grid-template-columns: repeat(3, 1fr);">
        <div class="stat-card">
          <div class="stat-value"><?= $pacientes->num_rows ?></div>
          <div class="stat-label">Pacientes en total</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $recientes = 0;
            $pacientes->data_seek(0);
            while ($p = $pacientes->fetch_assoc()) {
                if ($p['ultima_consulta'] && strtotime($p['ultima_consulta']) > strtotime('-30 days')) {
                    $recientes++;
                }
            }
            echo $recientes;
            ?>
          </div>
          <div class="stat-label">Activos este mes</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">
            <?php
            $totalCitas = 0;
            $pacientes->data_seek(0);
            while ($p = $pacientes->fetch_assoc()) {
                $totalCitas += $p['total_citas'];
            }
            echo $totalCitas;
            ?>
          </div>
          <div class="stat-label">Consultas brindadas</div>
        </div>
      </div>

      <?php if ($pacientes->num_rows > 0): 
        $pacientes->data_seek(0);
      ?>
        <div class="pacientes-grid">
          <?php while ($p = $pacientes->fetch_assoc()): 
            $color = $colores[crc32($p['nombre']) % count($colores)];
            $edad = $p['fecha_nacimiento'] ? (date('Y') - date('Y', strtotime($p['fecha_nacimiento']))) . ' años' : 'No registrada';
            $ultimaCita = $p['ultima_cita'] ? date('d/m/Y', strtotime($p['ultima_cita'])) : 'Sin consultas previas';
          ?>
            <div class="paciente-card">
              <div class="paciente-avatar" style="background: <?= $color ?>">
                <?= strtoupper(substr($p['nombre'], 0, 2)) ?>
              </div>
              <div class="paciente-info">
                <h3><?= htmlspecialchars($p['nombre']) ?></h3>
                <p class="email"><?= htmlspecialchars($p['email']) ?></p>
                <?php if ($p['telefono']): ?>
                  <p class="phone">Tel: <?= htmlspecialchars($p['telefono']) ?></p>
                <?php endif; ?>
                <p class="details">
                  Edad: <?= $edad ?> &bull; 
                  Sangre: <?= $p['tipo_sangre'] ?: 'N/D' ?> &bull;
                  Citas: <?= $p['total_citas'] ?>
                </p>
                <p class="last-visit">Última visita: <?= $ultimaCita ?></p>
                <?php if ($p['alergias']): ?>
                  <p class="alergies">Alergias: <?= htmlspecialchars(substr($p['alergias'], 0, 50)) ?></p>
                <?php endif; ?>
              </div>
              <div class="paciente-actions">
                <a href="<?= BASE_URL ?>/pages/historial/ver.php?paciente_id=<?= $p['paciente_id'] ?>&doctor_id=<?= $doctor_id ?>" 
                   class="btn btn-sm btn-primary">Expediente</a>
                <a href="<?= BASE_URL ?>/pages/citas/agendar.php?paciente_id=<?= $p['paciente_id'] ?>" 
                   class="btn btn-sm btn-outline">Agendar cita</a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <p>Aún no tienes pacientes registrados en tu historial</p>
          <p class="sub">Aparecerán aquí automáticamente conforme vayas atendiendo tus citas</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.pacientes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 20px;
}

.paciente-card {
  background: white;
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 24px;
  display: flex;
  gap: 16px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.02);
}

.paciente-card:hover {
  border-color: var(--emerald, #059669);
  box-shadow: 0 15px 30px rgba(5,150,105,.08);
  transform: translateY(-4px);
}

.paciente-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 800;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 8px 16px rgba(0,0,0,.1);
}

.paciente-info {
  flex: 1;
}

.paciente-info h3 {
  font-size: 16px;
  font-weight: 800;
  margin-bottom: 4px;
  color: var(--text);
}

.paciente-info .email,
.paciente-info .phone {
  font-size: 13px;
  color: var(--muted);
  margin: 2px 0;
}

.paciente-info .details {
  font-size: 12px;
  color: var(--muted);
  margin-top: 8px;
  font-weight: 600;
}

.paciente-info .last-visit {
  font-size: 12px;
  color: var(--emerald, #059669);
  margin-top: 4px;
  font-weight: 600;
}

.paciente-info .alergies {
  font-size: 12px;
  color: #e11d48;
  margin-top: 4px;
  font-weight: 600;
}

.paciente-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  justify-content: center;
}

.btn-sm {
  padding: 8px 14px;
  font-size: 12px;
  border-radius: 10px;
}

.stat-card {
  background: white;
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 24px;
  text-align: center;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,.02);
}

.stat-value {
  font-size: 32px;
  font-weight: 800;
  color: var(--emerald, #059669);
  letter-spacing: -1px;
}

.stat-label {
  font-size: 13px;
  color: var(--muted);
  margin-top: 6px;
  font-weight: 600;
}

.empty-state .sub {
  font-size: 13px;
  color: var(--muted);
  margin-top: 8px;
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>