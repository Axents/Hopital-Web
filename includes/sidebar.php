<?php
$rol    = $_SESSION['rol']    ?? 'paciente';
$nombre = $_SESSION['nombre'] ?? 'Usuario';
$inicial = strtoupper(substr($nombre, 0, 2));
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';

$navAdmin = [
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-dashboard.svg',    'label'=>'Dashboard',      'url'=>'pages/admin/dashboard.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-user.svg',     'label'=>'Usuarios',       'url'=>'pages/admin/users.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-doctor.svg',     'label'=>'Doctores',       'url'=>'pages/doctores/listar.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-citas.svg',        'label'=>'Citas',          'url'=>'pages/citas/ver.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-reports.svg',     'label'=>'Reportes',       'url'=>'pages/admin/reports.php'],
];

$navDoctor = [
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-dashboard.svg',    'label'=>'Dashboard',      'url'=>'pages/doctor/dashboard.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-citas.svg',        'label'=>'Agenda del Dia', 'url'=>'pages/doctor/citas.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-user.svg',     'label'=>'Mis Pacientes',  'url'=>'pages/doctor/pacientes.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-historial.svg',    'label'=>'Historial Medico','url'=>'pages/historial/ver.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-settings.svg','label'=>'Configuracion',  'url'=>'pages/doctor/perfil.php'],
];

$navPaciente = [
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-dashboard.svg',    'label'=>'Dashboard',      'url'=>'pages/paciente/dashboard.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-citas.svg',        'label'=>'Mis Citas',      'url'=>'pages/citas/ver.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-historial.svg',    'label'=>'Historial Medico','url'=>'pages/paciente/historial.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-user.svg',       'label'=>'Mi Perfil',      'url'=>'pages/paciente/perfil.php'],
  ['icon'=> BASE_URL . '/assets/img/svg/sidebar-settings.svg','label'=>'Configuracion',  'url'=>'pages/paciente/config.php'],
];

$nav = match($rol) {
  'admin'  => $navAdmin,
  'doctor' => $navDoctor,
  default  => $navPaciente,
};
?>
<aside class="sidebar">

  <div class="sidebar-brand">
    <div>
      <div class="sidebar-brand-text">Hospital Palacio de la salud</div>
      <div class="sidebar-brand-role"><?= ucfirst($rol) ?></div>
    </div>
  </div>

  <button onclick="toggleTheme()">Tema oscuro</button>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Menu</div>
    <?php foreach ($nav as $item):
      $active = str_contains($currentUrl, $item['url']) ? 'active' : '';
    ?>
      <a href="<?= BASE_URL ?>/<?= $item['url'] ?>" class="nav-item <?= $active ?>">
        <span class="nav-icon">
          <img src="<?= $item['icon'] ?>" width="16" height="16" alt="<?= $item['label'] ?>">
        </span>
        <?= $item['label'] ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= $inicial ?></div>
      <div class="sidebar-user-info">
        <div class="name"><?= htmlspecialchars($nombre) ?></div>
        <div class="role"><?= ucfirst($rol) ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
      <img src="<?= BASE_URL ?>/assets/img/svg/sidebar-logout.svg" width="16" height="16" alt="logout">
      Cerrar sesion
    </a>
  </div>

</aside>

<style>
  .sidebar {
    width: var(--sidebar-w);
    background: #ffffff;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 50;
    overflow-y: auto;
  }

  .sidebar-brand {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar-brand-text {
    font-size: 15px; font-weight: 700; color: #0f172a;
    line-height: 1.2;
  }

  .sidebar-brand-role {
    font-size: 11px; color: #94a3b8;
  }

  .sidebar-nav { flex: 1; padding: 12px 0; }

  .nav-section-label {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #94a3b8; padding: 12px 20px 6px;
  }

  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px;
    font-size: 13px; font-weight: 500;
    color: #64748b;
    transition: all .15s;
    text-decoration: none;
    border-radius: 8px;
    margin: 2px 8px;
  }

  .nav-item:hover {
    background: #f1f5f9;
    color: #0f172a;
    text-decoration: none;
  }

  .nav-item.active {
    background: #eff6ff;
    color: #2563eb;
    font-weight: 600;
  }

  .nav-item img {
    filter: brightness(0) invert(0.5);
    transition: none;
  }

  .nav-item.active img {
    filter: invert(37%) sepia(93%) saturate(1352%) hue-rotate(204deg) brightness(95%) contrast(96%);
  }

  .nav-icon {
    width: 20px; height: 20px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid #e2e8f0;
  }

  .sidebar-user {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 12px;
  }

  .sidebar-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #2563eb;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    flex-shrink: 0;
  }

  .sidebar-user-info .name {
    font-size: 13px; font-weight: 600; color: #0f172a;
    line-height: 1.2;
  }

  .sidebar-user-info .role {
    font-size: 11px; color: #94a3b8;
  }

  .logout-btn {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #ef4444;
    font-weight: 500;
    text-decoration: none;
    transition: color .15s;
    padding: 6px 0;
  }

  .logout-btn:hover { color: #dc2626; text-decoration: none; }

  .logout-btn img {
    filter: invert(27%) sepia(93%) saturate(1352%) hue-rotate(335deg) brightness(90%) contrast(119%);
  }
</style>