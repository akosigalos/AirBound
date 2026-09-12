<?php
session_start();
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Guest');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Laws & Policies — AIR-BOUND</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="bg-ambient text-slate-900 antialiased page-enter">
    <?php include __DIR__ . '/partials/header-landing.php'; ?>

    <main class="max-w-7xl mx-auto px-6 p-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-2xl font-semibold">Laws & Policies for Air Quality Management</h2>
          <p class="text-sm text-slate-600">Resources and references on air quality laws and policies.</p>
        </div>
        <div>
          <a href="monitoring.php" class="text-sm px-3 py-2 rounded bg-emerald-600 text-white">Air Quality Monitoring</a>
        </div>
      </div>

      <section class="bg-white rounded-lg p-4 shadow">
        <p class="text-sm text-slate-700">This page lists laws, regulations, and guidance resources related to air quality management. Use the button above to go directly to the Real-Time Monitoring dashboard.</p>
      </section>
    </main>
    <!-- Dropdown handled centrally by header script (portal) -->
  </body>
</html>
