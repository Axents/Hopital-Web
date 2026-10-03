<?php
$pageTitle = 'Mis Citas';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$mensaje = '';

if ($rol === 'paciente') {
    $pacienteRow = $conn->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
    $pacienteRow->bind_param('i', $usuario_id);
    $pacienteRow->execute();
    $paciente = $pacienteRow->get_result()->fetch_assoc();
    $paciente_id = $paciente['id'] ?? 0;
    
    $citas = $conn->prepare("
        SELECT c.*, 
               u.nombre AS doctor_nombre,
               e.nombre AS especialidad,
               d.telefono AS doctor_telefono
        FROM citas c
        JOIN doctores d ON c.doctor_id = d.id
        JOIN usuarios u ON d.usuario_id = u.id
        LEFT JOIN especialidades e ON d.especialidad_id = e.id
        WHERE c.paciente_id = ?
        ORDER BY c.fecha DESC, c.hora DESC
    ");
    $citas->bind_param('i', $paciente_id);
} elseif ($rol === 'doctor') {
    $doctorRow = $conn->prepare("SELECT id FROM doctores WHERE usuario_id = ?");
    $doctorRow->bind_param('i', $usuario_id);
    $doctorRow->execute();
    $doctor = $doctorRow->get_result()->fetch_assoc();
    $doctor_id = $doctor['id'] ?? 0;
    
    $citas = $conn->prepare("
        SELECT c.*, 
               u.nombre AS paciente_nombre,
               p.telefono AS paciente_telefono,
               p.fecha_nacimiento
        FROM citas c
        JOIN pacientes pa ON c.paciente_id = pa.id
        JOIN usuarios u ON pa.usuario_id = u.id
        LEFT JOIN pacientes p ON pa.id = p.id
        WHERE c.doctor_id = ?
        ORDER BY c.fecha DESC, c.hora DESC
    ");
    $citas->bind_param('i', $doctor_id);
} else {
    redirect('dashboard.php');
}

$citas->execute();
$citasResult = $citas->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_cita'])) {
    $cita_id = $_POST['cita_id'];
    
    if ($rol === 'paciente') {
        $update = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ? AND paciente_id = ?");
        $update->bind_param('ii', $cita_id, $paciente_id);
    } else {
        $update = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ? AND doctor_id = ?");
        $update->bind_param('ii', $cita_id, $doctor_id);
    }
    
    if ($update->execute()) {
        $mensaje = '<div class="alert-success-modern">Cita cancelada correctamente</div>';
        header("Location: ver.php?success=1");
        exit;
    }
}

$success = isset($_GET['success']);
$colores = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
?>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Mis Citas</h1>
        <p>Gestiona tus citas medicas</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($success): ?>
        <div class="alert-success-modern">Cita cancelada correctamente</div>
      <?php endif; ?>

      <?php
      $totalCitas = $citasResult->num_rows;
      $citasPendientes = 0;
      $citasConfirmadas = 0;
      $citasCompletadas = 0;
      $citasCanceladas = 0;
      
      $citasResult->data_seek(0);
      while ($c = $citasResult->fetch_assoc()) {
          switch($c['estado']) {
              case 'pendiente': $citasPendientes++; break;
              case 'confirmada': $citasConfirmadas++; break;
              case 'completada': $citasCompletadas++; break;
              case 'cancelada': $citasCanceladas++; break;
          }
      }
      $citasResult->data_seek(0);
      ?>
      
      <div class="stats-citas-grid">
        <div class="stat-cita-card total">
          <div class="stat-cita-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </div>
          <div class="stat-cita-info">
            <span class="stat-cita-value"><?= $totalCitas ?></span>
            <span class="stat-cita-label">Total Citas</span>
          </div>
        </div>
        <div class="stat-cita-card pending">
          <div class="stat-cita-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
          </div>
          <div class="stat-cita-info">
            <span class="stat-cita-value"><?= $citasPendientes ?></span>
            <span class="stat-cita-label">Pendientes</span>
          </div>
        </div>
        <div class="stat-cita-card confirmed">
          <div class="stat-cita-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <div class="stat-cita-info">
            <span class="stat-cita-value"><?= $citasConfirmadas ?></span>
            <span class="stat-cita-label">Confirmadas</span>
          </div>
        </div>
        <div class="stat-cita-card completed">
          <div class="stat-cita-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>
          <div class="stat-cita-info">
            <span class="stat-cita-value"><?= $citasCompletadas ?></span>
            <span class="stat-cita-label">Completadas</span>
          </div>
        </div>
      </div>

      <!-- Boton agendar -->
      <div class="action-bar">
        <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn-agendar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          Agendar Nueva Cita
        </a>
      </div>

      <!-- Lista de citas -->
      <?php if ($citasResult->num_rows > 0): ?>
        <div class="citas-container">
          <?php while ($c = $citasResult->fetch_assoc()): 
            $color = $colores[crc32($rol === 'paciente' ? $c['doctor_nombre'] : $c['paciente_nombre']) % count($colores)];
            $hora12 = date('h:i A', strtotime($c['hora']));
            $fechaFormateada = date('d/m/Y', strtotime($c['fecha']));
            
            $estadoClasses = [
                'pendiente' => 'estado-pendiente',
                'confirmada' => 'estado-confirmada',
                'completada' => 'estado-completada',
                'cancelada' => 'estado-cancelada'
            ];
            $estadoLabels = [
                'pendiente' => 'Pendiente',
                'confirmada' => 'Confirmada',
                'completada' => 'Completada',
                'cancelada' => 'Cancelada'
            ];
            
            $esFuturo = strtotime($c['fecha']) >= strtotime(date('Y-m-d'));
          ?>
            <div class="cita-card">
              <div class="cita-card-left">
                <div class="cita-fecha">
                  <span class="fecha-dia"><?= date('d', strtotime($c['fecha'])) ?></span>
                  <span class="fecha-mes"><?= date('M', strtotime($c['fecha'])) ?></span>
                </div>
              </div>
              
              <div class="cita-card-middle">
                <?php if ($rol === 'paciente'): ?>
                  <div class="cita-doctor">
                    <div class="doctor-avatar-cita" style="background: <?= $color ?>">
                      <?= strtoupper(substr($c['doctor_nombre'], 0, 2)) ?>
                    </div>
                    <div class="doctor-info-cita">
                      <h3>Dr. <?= htmlspecialchars($c['doctor_nombre']) ?></h3>
                      <span class="especialidad-cita"><?= htmlspecialchars($c['especialidad'] ?? 'Medicina General') ?></span>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="cita-doctor">
                    <div class="doctor-avatar-cita" style="background: <?= $color ?>">
                      <?= strtoupper(substr($c['paciente_nombre'], 0, 2)) ?>
                    </div>
                    <div class="doctor-info-cita">
                      <h3><?= htmlspecialchars($c['paciente_nombre']) ?></h3>
                      <span class="especialidad-cita"><?= $c['paciente_telefono'] ? 'Tel: ' . htmlspecialchars($c['paciente_telefono']) : 'Sin telefono' ?></span>
                    </div>
                  </div>
                <?php endif; ?>
                
                <div class="cita-detalles">
                  <div class="detalle-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/>
                      <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span><?= $hora12 ?></span>
                  </div>
                  <?php if ($c['motivo']): ?>
                    <div class="detalle-item motivo">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                      </svg>
                      <span><?= htmlspecialchars(substr($c['motivo'], 0, 50)) ?></span>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
              
              <div class="cita-card-right">
                <div class="cita-estado">
                  <span class="estado-badge <?= $estadoClasses[$c['estado']] ?>">
                    <?= $estadoLabels[$c['estado']] ?>
                  </span>
                </div>
                
                <?php if ($c['estado'] === 'pendiente' && $esFuturo && ($rol === 'paciente' || $rol === 'doctor')): ?>
                  <form method="POST" class="cancelar-form">
                    <input type="hidden" name="cita_id" value="<?= $c['id'] ?>">
                    <button type="submit" name="cancelar_cita" class="btn-cancelar" onclick="return confirm('¿Esta seguro que desea cancelar esta cita?')">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                      Cancelar
                    </button>
                  </form>
                <?php endif; ?>
                
                <?php if ($rol === 'doctor' && $c['estado'] !== 'cancelada'): ?>
                  <a href="<?= BASE_URL ?>/pages/historial/agregar.php?cita_id=<?= $c['id'] ?>" class="btn-ver-historial">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="3"/>
                      <path d="M22 12c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2s10 4.48 10 10z"/>
                    </svg>
                    Historial
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-state-citas">
          <div class="empty-icon-citas">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </div>
          <h3>No tienes citas agendadas</h3>
          <p>Agenda tu primera cita medica y cuida tu salud</p>
          <a href="<?= BASE_URL ?>/pages/citas/agendar.php" class="btn-agendar-primera">Agendar primera cita</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
.content {
  padding: 28px 32px;
  background: #f8fafc;
  min-height: calc(100vh - 70px);
}

/* Alertas */
.alert-success-modern {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #15803d;
  padding: 14px 20px;
  border-radius: 12px;
  margin-bottom: 24px;
  font-size: 14px;
}

/* Stats Citas Grid */
.stats-citas-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 28px;
}

.stat-cita-card {
  background: white;
  border-radius: 18px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  border: 1px solid #eef2f6;
  transition: all 0.3s ease;
}

.stat-cita-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
}

.stat-cita-card.total .stat-cita-icon {
  background: #eff6ff;
  color: #2563eb;
}

.stat-cita-card.pending .stat-cita-icon {
  background: #fef3c7;
  color: #d97706;
}

.stat-cita-card.confirmed .stat-cita-icon {
  background: #dcfce7;
  color: #16a34a;
}

.stat-cita-card.completed .stat-cita-icon {
  background: #e0e7ff;
  color: #4f46e5;
}

.stat-cita-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-cita-info {
  flex: 1;
}

.stat-cita-value {
  display: block;
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.stat-cita-label {
  display: block;
  font-size: 12px;
  color: #64748b;
  margin-top: 4px;
}

/* Action Bar */
.action-bar {
  margin-bottom: 28px;
  display: flex;
  justify-content: flex-end;
}

.btn-agendar {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 12px 24px;
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: white;
  border-radius: 14px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
}

.btn-agendar:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Citas Container */
.citas-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.cita-card {
  background: white;
  border-radius: 18px;
  border: 1px solid #eef2f6;
  display: flex;
  overflow: hidden;
  transition: all 0.2s ease;
}

.cita-card:hover {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
  border-color: transparent;
}

.cita-card-left {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  padding: 20px 16px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 80px;
}

.cita-fecha {
  text-align: center;
  color: white;
}

.fecha-dia {
  display: block;
  font-size: 28px;
  font-weight: 800;
  line-height: 1;
}

.fecha-mes {
  display: block;
  font-size: 12px;
  font-weight: 600;
  margin-top: 4px;
  text-transform: uppercase;
}

.cita-card-middle {
  flex: 1;
  padding: 18px 20px;
}

.cita-doctor {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 14px;
}

.doctor-avatar-cita {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 700;
  color: white;
}

.doctor-info-cita h3 {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.especialidad-cita {
  font-size: 12px;
  color: #64748b;
}

.cita-detalles {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.detalle-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #475569;
}

.detalle-item svg {
  color: #94a3b8;
}

.detalle-item.motivo {
  color: #64748b;
  font-size: 12px;
}

.cita-card-right {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
  justify-content: center;
  min-width: 130px;
}

.estado-badge {
  display: inline-block;
  padding: 5px 14px;
  border-radius: 30px;
  font-size: 11px;
  font-weight: 600;
}

.estado-pendiente {
  background: #fef3c7;
  color: #d97706;
}

.estado-confirmada {
  background: #dcfce7;
  color: #16a34a;
}

.estado-completada {
  background: #e0e7ff;
  color: #4f46e5;
}

.estado-cancelada {
  background: #fee2e2;
  color: #dc2626;
}

.btn-cancelar {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: transparent;
  border: 1.5px solid #fee2e2;
  border-radius: 10px;
  color: #dc2626;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancelar:hover {
  background: #fee2e2;
  border-color: #dc2626;
}

.btn-ver-historial {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  background: #f1f5f9;
  border-radius: 10px;
  color: #2563eb;
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-ver-historial:hover {
  background: #e2e8f0;
}

/* Empty State */
.empty-state-citas {
  text-align: center;
  padding: 60px 24px;
  background: white;
  border-radius: 20px;
  border: 1px solid #eef2f6;
}

.empty-icon-citas {
  width: 120px;
  height: 120px;
  margin: 0 auto 24px;
  background: #f1f5f9;
  border-radius: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
}

.empty-state-citas h3 {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.empty-state-citas p {
  color: #64748b;
  margin-bottom: 24px;
}

.btn-agendar-primera {
  display: inline-block;
  padding: 12px 28px;
  background: #2563eb;
  color: white;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-agendar-primera:hover {
  background: #1d4ed8;
  transform: translateY(-2px);
}

@media (max-width: 900px) {
  .stats-citas-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .content {
    padding: 20px;
  }
  
  .cita-card {
    flex-direction: column;
  }
  
  .cita-card-left {
    flex-direction: row;
    justify-content: space-between;
    padding: 12px 20px;
  }
  
  .cita-fecha {
    display: flex;
    align-items: baseline;
    gap: 10px;
  }
  
  .fecha-dia {
    font-size: 20px;
  }
  
  .cita-card-right {
    flex-direction: row;
    justify-content: space-between;
    border-top: 1px solid #eef2f6;
  }
  
  .stats-citas-grid {
    grid-template-columns: 1fr;
  }
  
  .action-bar {
    justify-content: stretch;
  }
  
  .btn-agendar {
    justify-content: center;
    width: 100%;
  }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>