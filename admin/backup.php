<?php
/**
 * Database backup and restore.
 *
 * Backup uses mysqldump to download an .sql file.
 * Restore imports an uploaded .sql file into the database.
 * (On XAMPP mysqldump is inside the folder xampp/mysql/bin.)
 */
$page_title = 'Backup & Restore';
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

// ---- Handle download (backup) ----
if (isset($_GET['download'])) {
    // Try to locate mysqldump (works on XAMPP and Linux).
    $candidates = ['mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe', '/usr/bin/mysqldump'];
    $dump = null;
    foreach ($candidates as $c) {
        if ($c === 'mysqldump' || file_exists($c)) { $dump = $c; break; }
    }
    $file = 'dams_db_backup_' . date('Ymd_His') . '.sql';
    $cmd = escapeshellarg($dump)
        . ' --host=' . escapeshellarg(DB_HOST)
        . ' --user=' . escapeshellarg(DB_USER)
        . (DB_PASS !== '' ? ' --password=' . escapeshellarg(DB_PASS) : '')
        . ' ' . escapeshellarg(DB_NAME);
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    passthru($cmd, $status);
    if ($status !== 0) {
        echo "\n-- Backup command failed. On XAMPP make sure mysqldump is available.\n";
    }
    exit;
}

// ---- Handle restore (upload .sql) ----
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!empty($_FILES['sqlfile']['tmp_name']) && $_FILES['sqlfile']['error'] === UPLOAD_ERR_OK) {
        $sql = file_get_contents($_FILES['sqlfile']['tmp_name']);
        try {
            db()->exec($sql);
            set_flash('success', 'Database restored successfully.');
        } catch (Throwable $e) {
            set_flash('error', 'Restore failed: ' . $e->getMessage());
        }
    } else {
        set_flash('error', 'Please choose a valid .sql file.');
    }
    redirect('admin/backup.php');
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-3 justify-content-center">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-download me-2"></i>Backup Database</div>
      <div class="card-body">
        <p class="text-muted small">Download a full copy of the <code><?= DB_NAME ?></code> database as an SQL file. Keep it in a safe place.</p>
        <a href="?download=1" class="btn btn-primary"><i class="bi bi-database-down me-1"></i>Download Backup</a>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-upload me-2"></i>Restore Database</div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <input type="file" name="sqlfile" accept=".sql" class="form-control" required>
          </div>
          <button class="btn btn-outline-danger" data-confirm="This will overwrite current data. Continue?"><i class="bi bi-database-up me-1"></i>Restore</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
