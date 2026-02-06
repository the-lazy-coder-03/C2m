<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$greeting = 'Hello from PHP!';
$items = array('Camera', 'Laptop', 'Sneakers');
$now = date('Y-m-d H:i:s');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PHP Test Page</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 2rem; color: #222; }
    .card { border: 1px solid #ddd; padding: 1rem; border-radius: 8px; max-width: 640px; }
    ul { padding-left: 1.2rem; }
    code { background: #f6f6f6; padding: 0.1rem 0.3rem; border-radius: 4px; }
  </style>
</head>
<body>
  <h1><?php echo $greeting; ?></h1>
  <div class="card">
    <p>Server time: <code><?php echo $now; ?></code></p>
    <p>Sample items (rendered from a PHP array):</p>
    <ul>
      <?php foreach ($items as $item): ?>
        <li><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</body>
</html>
