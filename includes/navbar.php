<nav class="navbar navbar-expand-lg navbar-custom navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="logo.png" alt="Pokhara Metropolitan Logo" style="height:48px; object-fit:contain;">
            <div class="navbar-brand-text">
                <span class="navbar-brand-title">Pokhara Metro</span>
                <span class="navbar-brand-subtitle">Waste Management System</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="nav-link fw-500" href="index.php" data-i18n="nav_home">Home</a>
                </li>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="login.php" data-i18n="nav_login">Login</a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-light btn-sm fw-600 px-3 py-2" href="register.php" data-i18n="nav_register" style="color:#023e8a;">
                            <i class="bi bi-person-plus me-1"></i>Register
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item ms-3">
                    <div class="btn-group" role="group" aria-label="Language">
                        <a href="#" class="lang-btn btn btn-outline-light btn-sm" data-lang="en">EN</a>
                        <a href="#" class="lang-btn btn btn-outline-light btn-sm" data-lang="np">ने</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
