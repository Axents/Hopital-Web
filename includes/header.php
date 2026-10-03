<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <?php if (isset($extraCss)): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $extraCss ?>">
  <?php endif; ?>
</head>
<body>
