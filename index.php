<?php
require_once __DIR__ . '/config/config.php';
if (isLoggedIn()) {
    redirect('pages/' . $_SESSION['rol'] . '/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hospital Palacio de la Salud</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --emerald: #059669;
      --emerald-dark: #047857;
      --emerald-light: #d1fae5;
      --teal: #0d9488;
      --dark: #090d16;
      --gray-muted: #64748b;
      --surface: #ffffff;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--dark);
      background: #fafafa;
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }

    nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
      background: rgba(250, 250, 250, 0.85);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(0,0,0,.06);
      padding: 0 60px;
      height: 85px;
      display: flex; align-items: center; justify-content: space-between;
    }

    .nav-brand {
      display: flex; align-items: center; gap: 14px;
      font-size: 20px; font-weight: 800; color: var(--dark);
      letter-spacing: -0.5px;
    }
    .nav-brand span {
      background: linear-gradient(135deg, var(--emerald), var(--teal));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .nav-links {
      display: flex; align-items: center; gap: 40px;
      list-style: none;
    }
    .nav-links a {
      font-size: 14px; font-weight: 600; color: var(--gray-muted);
      transition: color .2s ease;
    }
    .nav-links a:hover { color: var(--emerald); }

    .nav-actions { display: flex; align-items: center; gap: 16px; }

    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 8px;
      padding: 12px 26px; border-radius: 50px;
      font-size: 14px; font-weight: 700; cursor: pointer;
      transition: all .3s cubic-bezier(0.16, 1, 0.3, 1); border: none; font-family: inherit;
    }
    .btn-ghost { background: transparent; color: var(--dark); }
    .btn-ghost:hover { background: rgba(0,0,0,.04); }
    .btn-primary { 
      background: var(--dark); 
      color: #fff;
      box-shadow: 0 10px 25px rgba(9,13,22,.15);
    }
    .btn-primary:hover { 
      background: var(--emerald);
      transform: translateY(-2px); 
      box-shadow: 0 15px 30px rgba(5,150,105,.25); 
    }
    .btn-outline { background: transparent; border: 2px solid var(--dark); color: var(--dark); border-radius: 50px; }
    .btn-outline:hover { background: var(--dark); color: white; }
    .btn-lg { padding: 16px 36px; font-size: 16px; }

    .hero {
      min-height: 100vh;
      padding: 140px 60px 80px;
      display: flex; align-items: center;
      background: radial-gradient(circle at 85% 15%, rgba(5,150,105,.07), transparent 40%),
                  radial-gradient(circle at 10% 90%, rgba(13,148,136,.06), transparent 40%),
                  #fafafa;
      position: relative;
    }

    .hero-inner {
      max-width: 1300px; margin: 0 auto; width: 100%;
      display: grid; grid-template-columns: 1.2fr 0.8fr;
      gap: 80px; align-items: center;
    }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--emerald-light); color: var(--emerald-dark);
      padding: 8px 18px; border-radius: 30px;
      font-size: 13px; font-weight: 800; text-transform: uppercase;
      letter-spacing: 0.08em; margin-bottom: 24px;
    }

    .hero h1 {
      font-size: clamp(40px, 5vw, 64px);
      font-weight: 800; line-height: 1.05;
      letter-spacing: -2px;
      margin-bottom: 24px;
      color: var(--dark);
    }

    .hero h1 .highlight { 
      background: linear-gradient(135deg, var(--emerald), var(--teal));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero p {
      font-size: 18px; color: var(--gray-muted);
      line-height: 1.7; margin-bottom: 40px;
      max-width: 520px;
    }

    .hero-actions { display: flex; gap: 16px; flex-wrap: wrap; }

    .hero-visual { position: relative; }

    .hero-img-wrap {
      width: 100%; aspect-ratio: 1/1;
      border-radius: 40px; overflow: hidden;
      box-shadow: 0 30px 60px rgba(0,0,0,.12);
      border: 8px solid white;
      transform: rotate(2deg);
      transition: transform 0.5s ease;
    }
    .hero-img-wrap:hover { transform: rotate(0deg); }

    .hero-img-wrap img {
      width: 100%; height: 100%; object-fit: cover;
    }

    .section {
      padding: 120px 60px;
      max-width: 1300px; margin: 0 auto;
    }

    .section-header { margin-bottom: 70px; }
    .section-tag {
      font-size: 12px; font-weight: 800; color: var(--emerald);
      text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 12px;
      display: block;
    }
    .section-header h2 {
      font-size: clamp(32px, 4vw, 48px);
      font-weight: 800; letter-spacing: -1.5px;
      color: var(--dark);
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 32px;
    }

    .service-card {
      background: var(--surface);
      border: 1px solid rgba(0,0,0,.06);
      border-radius: 24px;
      padding: 40px;
      transition: all .4s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 10px 30px rgba(0,0,0,.02);
    }
    .service-card:hover {
      border-color: var(--emerald);
      box-shadow: 0 20px 40px rgba(5,150,105,.08);
      transform: translateY(-8px);
    }

    .service-icon {
      width: 64px; height: 64px;
      background: var(--emerald-light);
      color: var(--emerald-dark);
      border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin-bottom: 24px;
    }

    .service-card h3 { font-size: 20px; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.5px; }
    .service-card p  { font-size: 15px; color: var(--gray-muted); line-height: 1.7; }

    .doctors-section {
      background: var(--dark);
      color: white;
      padding: 120px 60px;
    }
    .doctors-inner { max-width: 1300px; margin: 0 auto; }
    .doctors-section .section-header h2 { color: white; }
    .doctors-section .section-tag { color: var(--teal); }

    .doctors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 32px;
      margin-top: 60px;
    }

    .doctor-card {
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 24px;
      padding: 36px 28px;
      text-align: center;
      transition: all .4s ease;
    }
    .doctor-card:hover {
      background: rgba(255,255,255,.06);
      border-color: var(--emerald);
      transform: translateY(-8px);
    }

    .doctor-avatar {
      width: 90px; height: 90px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 32px; font-weight: 800; color: white;
      margin: 0 auto 20px;
      box-shadow: 0 15px 30px rgba(0,0,0,.3);
    }

    .doctor-card h3 { font-size: 18px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.3px; }
    .doctor-card .spec { font-size: 14px; color: #94a3b8; margin-bottom: 20px; font-weight: 500; }
    .doctor-card .avail {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 700; color: #34d399;
      background: rgba(52,211,153,.1); padding: 6px 16px; border-radius: 30px;
    }
    .doctor-card .avail::before { content: ''; width: 6px; height: 6px; background: #34d399; border-radius: 50%; }

    .cta-section {
      padding: 140px 60px;
      text-align: center;
      background: linear-gradient(135deg, var(--emerald) 0%, var(--teal) 100%);
      color: white;
    }
    .cta-section h2 {
      font-size: clamp(36px, 4.5vw, 52px);
      font-weight: 800; letter-spacing: -1.5px;
      margin-bottom: 20px;
    }
    .cta-section p {
      font-size: 18px; opacity: .9;
      margin-bottom: 40px; max-width: 560px; margin-left: auto; margin-right: auto;
    }
    .btn-dark { 
      background: var(--dark); color: white; font-weight: 800; border-radius: 50px;
      box-shadow: 0 20px 40px rgba(0,0,0,.2);
    }
    .btn-dark:hover { background: #fff; color: var(--dark); transform: translateY(-3px); }

    footer {
      background: #05070c; color: #64748b;
      padding: 60px;
      text-align: center;
      font-size: 14px;
      border-top: 1px solid rgba(255,255,255,.05);
    }
    footer .footer-brand {
      font-size: 20px; font-weight: 800; color: white; margin-bottom: 10px;
    }

    @media (max-width: 768px) {
      nav { padding: 0 20px; }
      .nav-links { display: none; }
      .hero-inner { grid-template-columns: 1fr; padding: 60px 0; gap: 40px; }
      .hero-visual { display: none; }
      .hero, .section, .doctors-section, .cta-section { padding-left: 20px; padding-right: 20px; }
    }
  </style>
</head>
<body>

<nav>
  <div class="nav-brand">
    <span>Hospital</span> Palacio de la Salud
  </div>
  <ul class="nav-links">
    <li><a href="#servicios">Especialidades</a></li>
    <li><a href="#doctores">Nuestros Medicos</a></li>
    <li><a href="#contacto">Contacto</a></li>
  </ul>
  <div class="nav-actions">
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-ghost">Iniciar sesion</a>
    <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Agendar una cita</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-inner">
    <div class="hero-content">
      <div class="hero-badge">Atencion con calidad humana</div>
      <h1>
        Cuidamos de ti y de los tuyos <br>
        con un trato <span class="highlight">cercano</span> y profesional
      </h1>
      <p>
        Queremos que te sientas en confianza desde el primer momento en que entras por la puerta. Aquí tu salud y tu tranquilidad son lo que mas nos importa.
      </p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-lg">
          Agendar mi cita
        </a>
        <a href="#servicios" class="btn btn-outline btn-lg">
          Ver especialidades
        </a>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-img-wrap">
        <img src="<?= BASE_URL ?>/assets/img/landing.jpg" alt="Imagen del hospital">
      </div>
    </div>
  </div>
</section>

<div id="servicios">
<div class="section">
  <div class="section-header">
    <span class="section-tag">En que podemos ayudarte</span>
    <h2>Especialidades pensadas para tu bienestar</h2>
  </div>
  <div class="services-grid">
    <?php
    $services = [
      ['<img src="'. BASE_URL .'/assets/img/svg/heart.svg" width="28">','Cardiologia','Revisamos y cuidamos la salud de tu corazon para que vivas con total tranquilidad.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/brain.svg" width="28">','Neurologia','Atencion y seguimiento para cualquier tema relacionado con tu sistema nervioso.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/baby.svg" width="28">','Pediatria','Un espacio dedicado al cuidado y crecimiento saludable de tus hijos con mucha paciencia.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/bone.svg" width="28">','Traumatologia','Te ayudamos a recuperarte de lesiones, dolores musculares o problemas en huesos y articulaciones.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/smile.svg" width="28">','Dermatologia','Cuidado integral para mantener tu piel sana, limpia y protegida todo el año.'],
      ['<img src="'. BASE_URL .'/assets/img/svg/stethoscope.svg" width="28">','Medicina General','El punto de partida para cualquier chequeo de rutina o dudas sobre tu salud general.'],
    ];
    foreach ($services as [$icon, $name, $desc]):
    ?>
    <div class="service-card">
      <div class="service-icon"><?= $icon ?></div>
      <h3><?= $name ?></h3>
      <p><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</div>

<!-- DOCTORES -->
<div id="doctores">
<div class="doctors-section">
  <div class="doctors-inner">
    <div class="section-header">
      <span class="section-tag">El equipo medico</span>
      <h2>Doctores listos para atenderte</h2>
    </div>
    <div class="doctors-grid">
      <?php
      $colors = ['#059669','#0d9488','#10b981','#0284c7','#6366f1','#8b5cf6'];
      $doctors = [];
      require_once __DIR__ . '/includes/db.php';
      $res = $conn->query("SELECT u.nombre, e.nombre AS especialidad FROM doctores d JOIN usuarios u ON d.usuario_id = u.id LEFT JOIN especialidades e ON d.especialidad_id = e.id WHERE d.disponible = 1 LIMIT 6");
      if ($res) while ($r = $res->fetch_assoc()) $doctors[] = $r;

      if (empty($doctors)) {
        $doctors = [
          ['nombre'=>'Dr. Andres','especialidad'=>'Cardiologia'],
          ['nombre'=>'Dra. Aylin Lopez','especialidad'=>'Neurologia'],
          ['nombre'=>'Dr. Mario Perez','especialidad'=>'Pediatria'],
          ['nombre'=>'Dra. Arnold Garcia','especialidad'=>'Dermatologia'],
          ['nombre'=>'Dr. Cano','especialidad'=>'Cardiologia']
        ];
      }

      foreach ($doctors as $i => $d):
        $inicial = strtoupper(substr($d['nombre'], 0, 2));
        $color = $colors[$i % count($colors)];
      ?>
      <div class="doctor-card">
        <div class="doctor-avatar" style="background:<?= $color ?>;"><?= $inicial ?></div>
        <h3><?= htmlspecialchars($d['nombre']) ?></h3>
        <div class="spec"><?= htmlspecialchars($d['especialidad'] ?? 'Medicina General') ?></div>
        <div class="avail">Disponible hoy</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
</div>

<div id="contacto">
<div class="cta-section">
  <h2>¿Necesitas ver a un medico pronto?</h2>
  <p>No esperes mas para sentirte bien. Aparta tu lugar en pocos clics y ven a visitarnos.</p>
  <a href="<?= BASE_URL ?>/login.php" class="btn btn-dark btn-lg">
    Agendar mi cita ahora
  </a>
</div>
</div>

<footer>
  <div class="footer-brand">Hospital Palacio de la Salud</div>
  <p>Desarrollado con mucho esfuerzo por: Aylin, Cano, Arnold, Nambo y Andrés.</p>
</footer>

</body>
</html>