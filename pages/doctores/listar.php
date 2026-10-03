<?php
$pageTitle = 'Nuestros Especialistas';
require_once __DIR__ . '/../../config/config.php';
requireLogin();
require_once __DIR__ . '/../../includes/db.php';

$doctores = $conn->query("
    SELECT d.id, u.nombre, u.email, e.nombre AS especialidad,
           d.telefono, d.disponible
    FROM doctores d
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    ORDER BY u.nombre
");
$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Directorio Médico</h1>
        <p>Conoce a los especialistas disponibles en el hospital</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">
      <?php if ($_SESSION['rol'] === 'admin'): ?>
      <div style="margin-bottom:24px;">
        <a href="<?= BASE_URL ?>/pages/doctores/registrar.php" class="btn btn-primary" style="background:var(--emerald); border-radius:50px; font-weight:700; padding: 12px 24px;">+ Registrar Nuevo Doctor</a>
      </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header">
          <div><h2>Nuestros médicos especialistas</h2><p>Listado general de profesionales de la salud</p></div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Médico</th>
                <th>Especialidad</th>
                <th>Correo electrónico</th>
                <th>Teléfono</th>
                <th>Estatus</th>
                <?php if ($_SESSION['rol'] === 'paciente'): ?>
                <th>Acción</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
            <?php $i = 0; while ($d = $doctores->fetch_assoc()):
              $ini = strtoupper(substr($d['nombre'], 0, 2));
              $color = $colores[$i++ % count($colores)];
            ?>
              <tr>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
                    <div>
                      <div class="td-primary">Dr. <?= htmlspecialchars($d['nombre']) ?></div>
                      <div class="td-muted">Cédula profesional verificada</div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($d['especialidad'] ?? 'Medicina General') ?></td>
                <td><?= htmlspecialchars($d['email']) ?></td>
                <td><?= htmlspecialchars($d['telefono'] ?? '—') ?></td>
                <td>
                  <span class="badge <?= $d['disponible'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $d['disponible'] ? 'Disponible' : 'No disponible' ?>
                  </span>
                </td>
                <?php if ($_SESSION['rol'] === 'paciente'): ?>
                <td>
                  <a href="<?= BASE_URL ?>/pages/citas/agendar.php?doctor_id=<?= $d['id'] ?>"
                     class="btn btn-primary btn-sm" style="background:var(--emerald); border-radius:8px; font-weight:600; padding: 6px 14px;">Agendar cita</a>
                </td>
                <?php endif; ?>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>