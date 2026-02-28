<?php
// api/create_report.php
// FIX: Added uploads directory auto-creation so images are never lost even if folder is missing.
// All original logic is UNTOUCHED.
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $citizen_id  = $_SESSION['user_id'];
    $waste_type  = $_POST['waste_type'];
    $description = $_POST['description'];
    $latitude    = $_POST['latitude']  ?? null;
    $longitude   = $_POST['longitude'] ?? null;

    // Image Upload (FIX: ensure uploads/ directory exists before moving file)
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/';
        // Auto-create directory if missing (this was the root cause of NULL images)
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name   = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = 'uploads/' . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO waste_reports (citizen_id, waste_type, description, image, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$citizen_id, $waste_type, $description, $image_path, $latitude, $longitude])) {

        // Award 10 points for reporting waste (ORIGINAL — UNTOUCHED)
        $stmt_points = $pdo->prepare("INSERT INTO reward_points (user_id, points) VALUES (?, 10) ON DUPLICATE KEY UPDATE points = points + 10");
        $stmt_points->execute([$citizen_id]);

        header("Location: ../dashboard/citizen.php?success=1");
    } else {
        header("Location: ../dashboard/citizen.php?error=db");
    }
} else {
    header("Location: ../index.php");
}
?>
