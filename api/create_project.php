<?php
// api/create_project.php
include '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
    $name = $_POST['project_name'];
    $description = $_POST['description'];
    $municipality = $_POST['municipality'];

    $stmt = $pdo->prepare("INSERT INTO infrastructure_projects (project_name, description, municipality) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $description, $municipality])) {
        // Auto-allocate some initial funds (simulation)
        // In real app, we would allocate from the pool of waste funds.
        $project_id = $pdo->lastInsertId();
        
        // Let's allocate $1000 startup fund as a demo
        $stmt_update = $pdo->prepare("UPDATE infrastructure_projects SET funded_amount = 1000 WHERE id = ?");
        $stmt_update->execute([$project_id]);

        header("Location: ../dashboard/admin.php?success=project_created");
    } else {
        header("Location: ../dashboard/admin.php?error=failed");
    }
}
?>
