<?php
// migrate.php — Run this ONCE from your browser: http://localhost/waste_system/migrate.php
// Then DELETE this file for security.
include 'config/db.php';

$results = [];

$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS home_number VARCHAR(50)" => "home_number",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS citizenship_number VARCHAR(50)" => "citizenship_number",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('pending','approved') DEFAULT 'approved'" => "status",
    "UPDATE users SET status = 'approved' WHERE status IS NULL OR status = ''" => "Set existing users to approved",
];

foreach ($migrations as $sql => $label) {
    try {
        $pdo->exec($sql);
        $results[] = ["ok", $label . " — ✅ Done"];
    } catch (PDOException $e) {
        $results[] = ["err", $label . " — ⚠️ " . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; background: #f0f9ff; padding: 40px; }</style>
</head>
<body>
<div class="container" style="max-width:640px;">
    <div class="card shadow-sm border-0 rounded-4 p-4">
        <h4 class="fw-700 text-center mb-4" style="color:#0369a1;">
            🛠️ Pokhara WIMS — Database Migration
        </h4>
        <?php foreach ($results as [$type, $msg]): ?>
            <div class="alert <?php echo $type === 'ok' ? 'alert-success' : 'alert-warning'; ?> py-2">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endforeach; ?>
        <div class="alert alert-info mt-3 small mb-0">
            <strong>⚠️ Important:</strong> Delete this file (<code>migrate.php</code>) after running it successfully.
        </div>
        <div class="text-center mt-3">
            <a href="index.php" class="btn btn-primary">← Return to Home</a>
        </div>
    </div>
</div>
</body>
</html>
