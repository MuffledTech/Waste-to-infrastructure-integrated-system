<?php
// api/update_status.php
// FIX: Added kg-based points award to citizen when collector marks waste as collected.
// RULE: Citizen earns 10 points per kilogram of waste collected.
// All original logic (status update, pickup record, image upload) is UNTOUCHED.
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'collector') {
    $report_id   = $_POST['report_id'];
    $collector_id = $_SESSION['user_id'];
    $weight      = $_POST['weight']; // weight in kg

    // Image Verification (ORIGINAL — UNTOUCHED)
    $image_path = null;
    if (isset($_FILES['verification_image']) && $_FILES['verification_image']['error'] == 0) {
        $upload_dir = '../uploads/';
        // Ensure uploads directory exists
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $file_name   = 'verify_' . time() . '_' . basename($_FILES['verification_image']['name']);
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['verification_image']['tmp_name'], $target_file)) {
            $image_path = 'uploads/' . $file_name;
        }
    }

    $pdo->beginTransaction();
    try {
        // Update Report Status (ORIGINAL — UNTOUCHED)
        $stmt = $pdo->prepare("UPDATE waste_reports SET status = 'collected', weight = ? WHERE id = ?");
        $stmt->execute([$weight, $report_id]);

        // Create Pickup Record (ORIGINAL — UNTOUCHED)
        $stmt_pickup = $pdo->prepare("INSERT INTO pickups (report_id, collector_id, verification_image) VALUES (?, ?, ?)");
        $stmt_pickup->execute([$report_id, $collector_id, $image_path]);

        // ===== FIX: Award points to the citizen =====
        // Rule: 10 points per kg of waste collected
        // First, find the citizen_id who submitted this report
        $citizen_stmt = $pdo->prepare("SELECT citizen_id FROM waste_reports WHERE id = ?");
        $citizen_stmt->execute([$report_id]);
        $citizen_id = $citizen_stmt->fetchColumn();

        if ($citizen_id) {
            $points_to_award = (int)round((float)$weight * 10); // 10 pts per kg
            if ($points_to_award < 1) $points_to_award = 1;     // minimum 1 point

            // INSERT or UPDATE reward_points row for this citizen
            $pts_stmt = $pdo->prepare(
                "INSERT INTO reward_points (user_id, points) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE points = points + VALUES(points)"
            );
            $pts_stmt->execute([$citizen_id, $points_to_award]);
        }
        // ===== END FIX =====

        $pdo->commit();
        header("Location: ../dashboard/collector.php?success=collected");
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ../dashboard/collector.php?error=failed");
    }
}
?>
