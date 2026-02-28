<?php
// api/approve_report.php
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
    $report_id = $_POST['report_id'];
    $action = $_POST['action']; // approve or reject

    if ($action == 'approve') {
        $pdo->beginTransaction();
        try {
            // Update Report Status
            $stmt = $pdo->prepare("UPDATE waste_reports SET status = 'approved' WHERE id = ?");
            $stmt->execute([$report_id]);

            // Calculate Funds (e.g., $2 per kg)
            $weight_stmt = $pdo->prepare("SELECT weight, citizen_id FROM waste_reports WHERE id = ?");
            $weight_stmt->execute([$report_id]);
            $report = $weight_stmt->fetch();
            $weight = $report['weight'];
            $citizen_id = $report['citizen_id'];
            
            $funds = $weight * 2; // $2 per Kg of waste processed

            // Add Funds to Fund Allocation (Pending Project Allocation - for now just tracking total funds generated)
            // In a complex system, we would allocate to specific projects here or later.
            // For now, we will just add citizen points.

            // Reward Points (10 points per kg)
            $points = $weight * 10;
            $stmt_points = $pdo->prepare("UPDATE reward_points SET points = points + ? WHERE user_id = ?");
            $stmt_points->execute([$points, $citizen_id]);

            $pdo->commit();
            header("Location: ../dashboard/admin.php?success=approved");
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: ../dashboard/admin.php?error=failed");
        }
    } else {
        $stmt = $pdo->prepare("UPDATE waste_reports SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$report_id]);
        header("Location: ../dashboard/admin.php?success=rejected");
    }
}
?>
