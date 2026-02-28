<?php
// api/approve_user.php
// Approves a pending user registration.
// Called from the Admin Dashboard via POST form submission.
// Does NOT modify any existing report/reward/marketplace logic.

include '../config/db.php';
session_start();

// Only accessible by admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];

    if ($user_id > 0) {
        $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND role = 'citizen'");
        $stmt->execute([$user_id]);
    }
}

// Redirect back to admin dashboard
header("Location: ../dashboard/admin.php");
exit;
