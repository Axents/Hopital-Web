<?php
$pageTitle = 'Reportes y Estadísticas';
require_once __DIR__ . '/../../config/config.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/db.php';

$porEstado = $conn->query("SELECT estado, COUNT(*) c FROM citas GROUP BY estado");
$estadoData = ['pendiente'=>0,'confirmada'=>0,'cancelada'=>0,'completada'=>0];
while ($r = $porEstado->fetch_assoc()) $estadoData[$r['estado']] = $r['c'];

$porMes = $conn->query("
    SELECT DATE_FORMAT(fecha, '%b %Y') mes, COUNT(*) c
    FROM citas
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
    ORDER BY fecha ASC
");
$meses = []; $citasMes = [];
while ($r = $porMes->fetch_assoc()) { $meses[] = $r['mes']; $citasMes[] = $r['c']; }

$topEsp = $conn->query("
    SELECT e.nombre, COUNT(c.id) c
    FROM citas c
    JOIN doctores d ON c.doctor_id = d.id
    JOIN especialidades e ON d.especialidad_id = e.id
    GROUP BY e.nombre ORDER BY c DESC LIMIT 5
");

$topDoc = $conn->query("
    SELECT u.nombre, COUNT(c.id) c, e.nombre AS especialidad
    FROM citas c
    JOIN doctores d ON c.doctor_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    LEFT JOIN especialidades e ON d.especialidad_id = e.id
    GROUP BY d.id ORDER BY c DESC LIMIT 5
");

$total = array_sum($estadoData);
$colores = ['#059669','#0d9488','#10b981','#0284c7','#6366f1'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Reportes del Sistema</h1>
        <p>Consulta el comportamiento general y flujo de atenciones</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>
    <div class="content">

      <div class="stats-grid" style="margin-bottom:24px;">
        <?php
        $estadoInfo = [
          'pendiente'  => ['label'=>'Por confirmar',  'badge'=>'badge-warning'],
          'confirmada' => ['label'=>'Confirmadas', 'badge'=>'badge-success'],
          'cancelada'  => ['label'=>'Canceladas',  'badge'=>'badge-danger'],
          'completada' => ['label'=>'Atendidas', 'badge'=>'badge-info'],
        ];
        foreach ($estadoData as $est => $num):
        ?>
        <div class="stat-card">
          <div>
            <div class="stat-value"><?= $num ?></div>
            <div class="stat-label"><?= $estadoInfo[$est]['label'] ?></div>
          </div>
          <span class="badge <?= $estadoInfo[$est]['badge'] ?>"><?= $total > 0 ? round($num/$total*100) : 0 ?>%</span>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card-grid">

        <div class="card">
          <div class="card-header">
            <div><h2>Citas por mes</h2><p>Comportamiento de los últimos 6 meses</p></div>
          </div>
          <div class="card-body">
            <?php if (empty($meses)): ?>
            <div class="empty-state"><p>Aún no hay suficientes datos para mostrar la gráfica</p></div>
            <?php else: ?>
            <canvas id="citasChart" height="200"></canvas>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div><h2>Especialidades preferidas</h2><p>Basado en el volumen de citas</p></div>
          </div>
          <div class="card-body">
            <?php $i = 0; while ($e = $topEsp->fetch_assoc()): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
              <div style="width:8px;height:8px;border-radius:50%;background:<?= $colores[$i++ % count($colores)] ?>;flex-shrink:0;"></div>
              <div style="flex:1;font-size:14px;font-weight:600;"><?= htmlspecialchars($e['nombre']) ?></div>
              <div style="font-size:14px;font-weight:700;color:var(--dark);"><?= $e['c'] ?> citas</div>
              <div style="width:80px;height:6px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                <div style="height:100%;background:<?= $colores[($i-1) % count($colores)] ?>;width:<?= $total > 0 ? round($e['c']/$total*100) : 0 ?>%;border-radius:3px;"></div>
              </div>
            </div>
            <?php endwhile; ?>
          </div>
        </div>

      </div>

      <div class="card">
        <div class="card-header">
          <div><h2>Doctores con mayor actividad</h2><p>Médicos que han atendido más consultas</p></div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Posición</th><th>Médico</th><th>Especialidad</th><th>Total de consultas</th></tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($d = $topDoc->fetch_assoc()):
              $ini = strtoupper(substr($d['nombre'], 0, 2));
              $color = $colores[($i-1) % count($colores)];
            ?>
              <tr>
                <td><strong>#<?= $i++ ?></strong></td>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
                    <div class="td-primary"><?= htmlspecialchars($d['nombre']) ?></div>
                  </div>
                </td>
                <td><?= htmlspecialchars($d['especialidad'] ?? 'Medicina General') ?></td>
                <td><strong><?= $d['c'] ?> consultas</strong></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
<?php if (!empty($meses)): ?>
const ctx = document.getElementById('citasChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($meses) ?>,
    datasets: [{
      label: 'Citas',
      data: <?= json_encode($citasMes) ?>,
      backgroundColor: '#059669',
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
      x: { grid: { display: false } }
    }
  }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>