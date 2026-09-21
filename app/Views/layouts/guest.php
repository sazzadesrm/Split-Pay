<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Split Pay') ?> · Split Pay</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="guest-body">
<div class="guest-wrap">
  <div class="guest-card-outer">
    <a href="<?= url('/') ?>" class="guest-brand"><span class="brand-mark">SP</span> Split Pay</a>
    <div class="card guest-card">
      <div class="card-body p-4 p-md-5">
        <?php if ($success = flash_get('success')): ?>
          <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($errorMsg = flash_get('error')): ?>
          <div class="alert alert-danger"><?= e($errorMsg) ?></div>
        <?php endif; ?>
        <?= $content ?>
      </div>
    </div>
    <p class="text-center text-muted small mt-3">Shared expenses without the spreadsheet chaos.</p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
