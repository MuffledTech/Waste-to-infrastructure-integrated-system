<?php
// register.php
include 'config/db.php';
session_start();

$error   = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name               = trim($_POST['name']);
    $email              = trim($_POST['email']);
    $password           = $_POST['password'];
    $municipality       = trim($_POST['municipality']);
    $home_number        = trim($_POST['home_number'] ?? '');
    $citizenship_number = trim($_POST['citizenship_number'] ?? '');
    $role               = 'citizen'; // Default registration is for citizens

    if (empty($name) || empty($email) || empty($password) || empty($municipality)) {
        $error = "Full Name, Email, Password, and Municipality are required.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = "Email already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Insert with status = 'pending' so admin can approve
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role, municipality, home_number, citizenship_number, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            if ($stmt->execute([$name, $email, $hashed_password, $role, $municipality, $home_number, $citizenship_number])) {
                $success = "Registration successful! Your account is <strong>pending admin approval</strong>. You will be notified once approved.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Pokhara Metropolitan Waste System</title>
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
    <div class="auth-card register-card">

        <!-- Logo -->
        <img src="logo.png" alt="Pokhara Metropolitan City Logo" class="auth-logo">
        <div class="auth-title">Citizen Registration</div>
        <div class="auth-subtitle">POKHARA METROPOLITAN WASTE SYSTEM</div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-check-circle-fill mt-1"></i>
                <span><?php echo $success; ?> <a href="login.php" class="alert-link">Go to Login</a></span>
            </div>
        <?php else: ?>

        <!-- Registration Form -->
        <form method="POST" action="" class="auth-form">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-person me-1 text-primary"></i>Full Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="name" class="form-control" placeholder="Your full name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-envelope me-1 text-primary"></i>Email Address <span class="text-danger">*</span>
                    </label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-lock me-1 text-primary"></i>Password <span class="text-danger">*</span>
                </label>
                <input type="password" name="password" class="form-control" placeholder="Create a strong password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-geo-alt me-1 text-primary"></i>Municipality <span class="text-danger">*</span>
                </label>
                <input type="text" name="municipality" class="form-control" placeholder="e.g. Pokhara Metropolitan" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-house me-1 text-primary"></i>Home Number
                    </label>
                    <input type="text" name="home_number" class="form-control" placeholder="e.g. 12-456">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-card-text me-1 text-primary"></i>Citizenship Number
                    </label>
                    <input type="text" name="citizenship_number" class="form-control" placeholder="e.g. 12-01-78-12345">
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-center gap-2 py-2 small">
                <i class="bi bi-info-circle-fill text-info"></i>
                <span>Your account will require <strong>admin approval</strong> before you can log in.</span>
            </div>

            <button type="submit" class="btn-auth-primary mt-2">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>

        <?php endif; ?>

        <div class="auth-divider"></div>
        <p class="text-center mb-0 small text-muted">
            Already have an account?
            <a href="login.php" class="auth-link">Login here</a>
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
