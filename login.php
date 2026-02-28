<?php
// login.php
include 'config/db.php';
session_start();

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Fetch user including status column
        $stmt = $pdo->prepare("SELECT id, name, password, role, municipality, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ===== ADMIN APPROVAL CHECK =====
            // Admins and collectors are always approved; only citizens need approval.
            if ($user['role'] === 'citizen' && ($user['status'] ?? 'pending') !== 'approved') {
                $error = "Your account is awaiting admin approval. Please check back later.";
            } else {
                // All checks passed — create session
                $_SESSION['user_id']     = $user['id'];
                $_SESSION['name']        = $user['name'];
                $_SESSION['role']        = $user['role'];
                $_SESSION['municipality'] = $user['municipality'];

                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: dashboard/admin.php");
                } elseif ($user['role'] == 'collector') {
                    header("Location: dashboard/collector.php");
                } else {
                    header("Location: dashboard/citizen.php");
                }
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Pokhara Metropolitan Waste System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-bg">
    <div class="auth-card">

        <!-- Logo -->
        <img src="logo.png" alt="Pokhara Metropolitan City Logo" class="auth-logo">
        <div class="auth-title">Pokhara Metropolitan</div>
        <div class="auth-subtitle">WASTE MANAGEMENT SYSTEM</div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="auth-form">
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-envelope me-1 text-primary"></i>Email Address
                </label>
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
            </div>
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-lock me-1 text-primary"></i>Password
                </label>
                <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-auth-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="auth-divider"></div>

        <p class="text-center mb-0 small text-muted">
            Don't have an account?
            <a href="register.php" class="auth-link">Register as Citizen</a>
        </p>
        <p class="text-center mt-2 mb-0 small text-muted">
            <a href="index.php" class="auth-link">
                <i class="bi bi-arrow-left me-1"></i>Back to Home
            </a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
