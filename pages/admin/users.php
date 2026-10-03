<?php
$pageTitle = 'Control de Usuarios';
require_once __DIR__ . '/../../config/config.php';
requireRole('admin');
require_once __DIR__ . '/../../includes/db.php';

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE usuarios SET activo = !activo WHERE id = $id");
    header("Location: " . BASE_URL . "/pages/admin/users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $id  = (int)$_POST['usuario_id'];
    $rol = $_POST['nuevo_rol'];
    if (in_array($rol, ['admin','doctor','paciente'])) {
        $stmt = $conn->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        $stmt->bind_param('si', $rol, $id);
        $stmt->execute();
    }
    header("Location: " . BASE_URL . "/pages/admin/users.php");
    exit;
}

$filtroRol = $_GET['rol'] ?? '';
$busqueda  = trim($_GET['q'] ?? '');

$where = "WHERE 1=1";
$params = [];
$types  = '';
if ($filtroRol) { $where .= " AND u.rol = ?"; $params[] = $filtroRol; $types .= 's'; }
if ($busqueda)  { $where .= " AND (u.nombre LIKE ? OR u.email LIKE ?)"; $params[] = "%$busqueda%"; $params[] = "%$busqueda%"; $types .= 'ss'; }

$stmt = $conn->prepare("SELECT u.id, u.nombre, u.email, u.rol, u.activo, u.created_at FROM usuarios u $where ORDER BY u.created_at DESC");
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$usuarios = $stmt->get_result();

$rolesLabel = ['admin'=>'Administrador','doctor'=>'Médico','paciente'=>'Paciente'];
$colores = ['admin'=>'#ef4444','doctor'=>'#0284c7','paciente'=>'#059669'];
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<div class="layout">
  <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <h1>Gestión de Usuarios</h1>
        <p>Administra las cuentas registradas en la plataforma</p>
      </div>
      <div class="topbar-right">
        <div class="notif-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-dot"></span>
        </div>
        <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['nombre'],0,2)) ?></div>
      </div>
    </div>

    <div class="content">

      <div class="card" style="margin-bottom:20px;">
        <div class="card-body" style="padding:16px 20px;">
          <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <input type="text" name="q" class="form-control" style="max-width:260px;"
                   placeholder="Buscar por nombre o correo..."
                   value="<?= htmlspecialchars($busqueda) ?>">
            <select name="rol" class="form-control" style="max-width:180px;">
              <option value="">Todos los perfiles</option>
              <option value="admin"    <?= $filtroRol === 'admin'    ? 'selected' : '' ?>>Administradores</option>
              <option value="doctor"   <?= $filtroRol === 'doctor'   ? 'selected' : '' ?>>Médicos</option>
              <option value="paciente" <?= $filtroRol === 'paciente' ? 'selected' : '' ?>>Pacientes</option>
            </select>
            <button type="submit" class="btn btn-primary">Buscar usuario</button>
            <?php if ($filtroRol || $busqueda): ?>
            <a href="<?= BASE_URL ?>/pages/admin/users.php" class="btn btn-outline">Ver todos</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div><h2>Listado general</h2></div>
          <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Crear nuevo usuario</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Usuario</th>
                <th>Perfil</th>
                <th>Estatus</th>
                <th>Fecha de registro</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php $count = 0; while ($u = $usuarios->fetch_assoc()): $count++;
              $ini = strtoupper(substr($u['nombre'], 0, 2));
              $color = $colores[$u['rol']] ?? '#64748b';
            ?>
              <tr>
                <td>
                  <div class="td-user">
                    <div class="table-avatar" style="background:<?= $color ?>;"><?= $ini ?></div>
                    <div>
                      <div class="td-primary"><?= htmlspecialchars($u['nombre']) ?></div>
                      <div class="td-muted"><?= htmlspecialchars($u['email']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= $rolesLabel[$u['rol']] ?? $u['rol'] ?></td>
                <td>
                  <span class="badge <?= $u['activo'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
                <td><div class="td-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></div></td>
                <td>
                  <a href="?toggle=<?= $u['id'] ?>"
                     class="btn btn-sm <?= $u['activo'] ? 'btn-outline' : 'btn-success' ?>"
                     onclick="return confirm('¿Estás seguro de que deseas <?= $u['activo'] ? 'desactivar' : 'activar' ?> este usuario?')">
                    <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
            <?php if ($count === 0): ?>
              <tr><td colspan="5">
                <div class="empty-state">
                  <div class="empty-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                  </div>
                  <p>No encontramos ningún usuario con esos criterios</p>
                </div>
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>