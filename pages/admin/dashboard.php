<?php
$pageTitle = 'Panel Administrativo';
require_once __DIR__ . '/../../config/config.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/db.php';

$stats = [];
foreach (['usuarios','doctores','pacientes','citas'] as $t) {
    $r = $conn->query("SELECT COUNT(*) c FROM $t");
    $stats[$t] = $r ? $r->fetch_assoc()['c'] : 0;
}

$hoy = $conn->query("SELECT COUNT(*) c FROM citas WHERE fecha = CURDATE()")->fetch_assoc()['c'];

$citasRecientes = $conn->query("
    SELECT c.id, c.fecha, c.hora, c.estado,
           up.nombre AS paciente, ud.nombre AS doctor,
           e.nombre AS especialidad
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN usuarios up ON p.usuario_id = up.id
    JOIN doctores d ON c.doctor_id = d.id
    JOIN usuarios ud ON d.usuario_id = ud.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    ORDER BY c.created_at DESC LIMIT 8
");

$pacientesRecientes = $conn->query("
    SELECT u.nombre, u.email, u.created_at
    FROM usuarios u
    WHERE u.rol = 'paciente'
    ORDER BY u.created_at DESC LIMIT 5
");

$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">

    <div class="topbar">
      <div class="topbar-left">
        <h1>Panel General</h1>
        <p>Bienvenido</p>
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot"></span>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">

      <div class="stats-grid">
        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-user.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $stats['usuarios'] ?></div>
            <div class="stat-label">Personas Registradas</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-doctor.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $stats['doctores'] ?></div>
            <div class="stat-label">Médicos en Equipo</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/users-total.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $stats['pacientes'] ?></div>
            <div class="stat-label">Pacientes Activos</div>
          </div>
        </div>

        <div class="stat-card">
          <div>
            <div class="stat-card-icon">
              <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-citas.svg" width="22" height="22" alt="">
            </div>
            <div class="stat-value"><?= $hoy ?></div>
            <div class="stat-label">Citas para Hoy</div>
          </div>
        </div>
      </div>

      <div class="card-grid">

        <div class="card">
          <div class="card-header">
            <div>
              <h2>Citas recientes</h2>
              <p>Últimos movimientos agendados en el hospital</p>
            </div>
            <a href="<?= BASE_URL ?>/pages/citas/ver.php" class="btn btn-outline btn-sm">Ver todas</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Paciente</th>
                  <th>Médico Asignado</th>
                  <th>Fecha y Hora</th>
                  <th>Estatus</th>
                </tr>
              </thead>
              <tbody>
              <?php while ($c = $citasRecientes->fetch_assoc()):
                $ini = strtoupper(substr($c['paciente'], 0, 2));
              ?>
                <tr>
                  <td>
                    <div class="td-user">
                      <div class="table-avatar" style="background:<?= $colores[crc32($c['paciente']) % count($colores)] ?>;"><?= $ini ?></div>
                      <div>
                        <div class="td-primary"><?= htmlspecialchars($c['paciente']) ?></div>
                        <div class="td-muted"><?= htmlspecialchars($c['especialidad'] ?? 'Medicina General') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($c['doctor']) ?></td>
                  <td>
                    <div class="td-primary"><?= date('d/m/Y', strtotime($c['fecha'])) ?></div>
                    <div class="td-muted"><?= $c['hora'] ?> hrs</div>
                  </td>
                  <td>
                    <?php
                    $map = ['pendiente'=>'badge-warning','confirmada'=>'badge-success',
                            'cancelada'=>'badge-danger','completada'=>'badge-info'];
                    ?>
                    <span class="badge <?= $map[$c['estado']] ?? 'badge-gray' ?>">
                      <?= ucfirst($c['estado']) ?>
                    </span>
                  </td>
                </tr>
              <?php endwhile; ?>
              <?php if ($stats['citas'] == 0): ?>
                <tr><td colspan="4">
                  <div class="empty-state">
                    <div class="empty-icon">
                      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <p>Aún no hay citas registradas en el sistema</p>
                  </div>
                </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div>
              <h2>Nuevos pacientes</h2>
              <p>Personas que acaban de unirse</p>
            </div>
            <a href="<?= BASE_URL ?>/pages/admin/users.php" class="btn btn-outline btn-sm">Ver lista</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Paciente</th>
                  <th>Fecha de alta</th>
                </tr>
              </thead>
              <tbody>
              <?php while ($p = $pacientesRecientes->fetch_assoc()):
                $ini = strtoupper(substr($p['nombre'], 0, 2));
              ?>
                <tr>
                  <td>
                    <div class="td-user">
                      <div class="table-avatar" style="background:<?= $colores[crc32($p['nombre']) % count($colores)] ?>;"><?= $ini ?></div>
                      <div>
                        <div class="td-primary"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="td-muted"><?= htmlspecialchars($p['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="td-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?></div>
                  </td>
                </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<style>
  .stat-card-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px;
    background: #ecfdf5;
  }
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>