<?php
// index.php - Landing Page
// Session check: redirect logged-in users to their dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') header("Location: dashboard/admin.php");
    elseif ($_SESSION['role'] == 'collector') header("Location: dashboard/collector.php");
    else header("Location: dashboard/citizen.php");
    exit;
}

// ===== DYNAMIC LANDING PAGE STATS =====
// Fetch real-time data from the database for the statistics section.
// These are read-only aggregate queries — no data is modified.
include 'config/db.php';

// 1. Total waste collected (kg) — sum of weight from all collected reports
$stat_waste = (int)$pdo->query("SELECT COALESCE(SUM(weight), 0) FROM waste_reports WHERE status = 'collected'")->fetchColumn();

// 2. Total active citizens registered in the system
$stat_citizens = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'citizen'")->fetchColumn();

// 3. Total infrastructure projects
$stat_projects = (int)$pdo->query("SELECT COUNT(*) FROM infrastructure_projects")->fetchColumn();
// ===== END DYNAMIC STATS =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Waste to  Infrastructure Integrated System - National Government of Nepal initiative for responsible waste management and infrastructure funding.">
    <title>wiis | Waste to Infrastructure Integrated System</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- AOS Animation CDN -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* Landing page — all colors from the global blue theme */
        .hero-title span { color: #90e0ef; }
    </style>


</head>
<body>

<!-- ===== PRELOADER ===== -->
<div id="preloader">
    <img src="logo.png" alt="Nepal Emblem">
    <p>Loading System...</p>
</div>

<!-- ===== NAVBAR ===== -->
<?php include 'includes/navbar.php'; ?>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section" id="home">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7" data-aos="fade-right">
                <div class="hero-badge">
                    <i class="bi bi-shield-check me-1"></i>
                    <span data-i18n="hero_badge">Pokhara Metropolitan City | Official System</span>
                </div>
                <h1 class="hero-title" data-i18n="hero_title">
                    Waste to Infrastructure <span>Integrated</span>  System
                </h1>
                <p class="hero-subtitle" data-i18n="hero_subtitle">
                    Join the national initiative to clean our cities and fund infrastructure projects through responsible waste management.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="register.php" class="hero-btn hero-btn-primary" data-i18n="btn_citizen">
                        <i class="bi bi-person-plus me-2"></i>Register as Citizen
                    </a>
                    <a href="login.php" class="hero-btn hero-btn-outline" data-i18n="btn_login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </a>
                </div>
                <div class="mt-5 d-flex gap-4 flex-wrap">
                    <div class="d-flex align-items-center gap-2 text-white-50">
                        <i class="bi bi-check-circle-fill" style="color:#90e0ef;"></i>
                        <small>Secure & Verified</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-white-50">
                        <i class="bi bi-check-circle-fill" style="color:#90e0ef;"></i>
                        <small>Real-time Tracking</small>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-white-50">
                        <i class="bi bi-check-circle-fill" style="color:#90e0ef;"></i>
                        <small>Reward Points System</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-center" data-aos="fade-left">
                <img src="logo.png"
                     alt="Emblem of Nepal"
                     class="hero-emblem floating-anim">
            </div>
        </div>
    </div>
</section>

<!-- ===== WAVE DIVIDER ===== -->
<div class="wave-divider" style="background: linear-gradient(135deg, #023e8a, #0096c7);">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 80">
        <path fill="#ffffff" fill-opacity="1" d="M0,64L80,58.7C160,53,320,43,480,42.7C640,43,800,53,960,58.7C1120,64,1280,64,1360,64L1440,64L1440,80L1360,80C1280,80,1120,80,960,80C800,80,640,80,480,80C320,80,160,80,80,80L0,80Z"></path>
    </svg>
</div>

<!-- ===== STATISTICS SECTION ===== -->
<section class="stats-section" id="stats">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-badge"><i class="bi bi-bar-chart-fill me-1"></i> NATIONAL IMPACT</span>
            <h2 class="fw-800 mt-2" style="font-size: 2.2rem; color: #0f172a;">System at a Glance</h2>
            <p class="text-muted">Real-time data from across the nation</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-trash3-fill"></i></div>
                    <div class="stat-number"><span class="counter" data-target="<?php echo $stat_waste; ?>"><?php echo $stat_waste; ?></span>+ kg</div>
                    <div class="stat-label" data-i18n="stat_waste">Total Waste Collected (kg)</div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-number"><span class="counter" data-target="<?php echo $stat_citizens; ?>"><?php echo $stat_citizens; ?></span>+</div>
                    <div class="stat-label" data-i18n="stat_users">Active Citizens</div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="stat-icon"><i class="bi bi-building-fill-check"></i></div>
                    <div class="stat-number"><span class="counter" data-target="<?php echo $stat_projects; ?>"><?php echo $stat_projects; ?></span>+</div>
                    <div class="stat-label" data-i18n="stat_infra">Infrastructure Projects</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="process-section" id="how-it-works">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-badge"><i class="bi bi-diagram-3-fill me-1"></i> PROCESS FLOW</span>
            <h2 class="fw-800 mt-2" style="font-size: 2.2rem; color: #0f172a;">How It Works</h2>
            <p class="text-muted">A simple 4-step process to turn waste into national wealth</p>
        </div>
        <div class="row align-items-center g-3">
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                <div class="process-card" data-step="1">
                    <div class="process-icon"><i class="bi bi-camera-fill"></i></div>
                    <h5 class="fw-700" data-i18n="step_1_title">Report Waste</h5>
                    <p class="text-muted small" data-i18n="step_1_desc">Snap a photo and upload waste location.</p>
                </div>
            </div>
            <div class="col-md-1 process-arrow d-none d-md-flex" data-aos="fade-up" data-aos-delay="150">
                <i class="bi bi-arrow-right-circle-fill" style="color: #0096c7;"></i>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                <div class="process-card" data-step="2">
                    <div class="process-icon"><i class="bi bi-truck-front-fill"></i></div>
                    <h5 class="fw-700" data-i18n="step_2_title">Collection</h5>
                    <p class="text-muted small" data-i18n="step_2_desc">Collectors verify and pick up the waste.</p>
                </div>
            </div>
            <div class="col-md-1 process-arrow d-none d-md-flex" data-aos="fade-up" data-aos-delay="250">
                <i class="bi bi-arrow-right-circle-fill" style="color: #0077b6;"></i>
            </div>
            <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                <div class="process-card" data-step="3">
                    <div class="process-icon"><i class="bi bi-building-fill"></i></div>
                    <h5 class="fw-700" data-i18n="step_3_title">Infrastructure</h5>
                    <p class="text-muted small" data-i18n="step_3_desc">Funds generated are used for public projects.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <div class="d-inline-flex align-items-center gap-2 bg-white rounded-pill px-4 py-2 shadow-sm">
                <i class="bi bi-trophy-fill text-warning fs-5"></i>
                <span class="fw-600 text-dark">Citizens earn <strong>Reward Points</strong> for every verified report!</span>
            </div>
        </div>
    </div>
</section>

<!-- ===== WAVE DIVIDER ===== -->
<div class="wave-divider" style="background: #f4f9fc;">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 80">
        <path fill="#023e8a" fill-opacity="1" d="M0,32L80,37.3C160,43,320,53,480,53.3C640,53,800,43,960,37.3C1120,32,1280,32,1360,32L1440,32L1440,80L1360,80C1280,80,1120,80,960,80C800,80,640,80,480,80C320,80,160,80,80,80L0,80Z"></path>
    </svg>
</div>

<!-- ===== NATIONAL IMPACT SECTION ===== -->
<section class="impact-section" id="impact">
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="section-badge" style="background: rgba(144,224,239,0.2); color: #90e0ef;">
                    <i class="bi bi-globe-asia-australia me-1"></i> NATIONAL VISION
                </span>
                <h2 class="fw-800 mt-3 text-white" style="font-size: 2.2rem;">
                    Building a <span style="color: #90e0ef;">Smarter</span>, Cleaner Nepal
                </h2>
                <p class="text-white-50 mt-3 mb-4" style="line-height: 1.9;">
                    Our system connects citizens, collectors, and administrators in a unified digital platform to manage waste efficiently and transparently — turning environmental challenges into national development opportunities.
                </p>
                <a href="register.php" class="hero-btn hero-btn-primary">
                    <i class="bi bi-person-plus me-2"></i>Join the Movement
                </a>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="impact-card">
                    <i class="bi bi-shield-lock-fill"></i>
                    <h5 class="text-white fw-600">Transparent & Accountable</h5>
                    <p class="text-white-50 small mb-0">Every report, collection, and fund allocation is tracked and publicly verifiable.</p>
                </div>
                <div class="impact-card">
                    <i class="bi bi-cpu-fill"></i>
                    <h5 class="text-white fw-600">Smart City Integration</h5>
                    <p class="text-white-50 small mb-0">Designed to integrate with Nepal's smart city and digital governance initiatives.</p>
                </div>
                <div class="impact-card">
                    <i class="bi bi-award-fill"></i>
                    <h5 class="text-white fw-600">Citizen Empowerment</h5>
                    <p class="text-white-50 small mb-0">Reward citizens for their contributions and build a culture of civic responsibility.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== WAVE DIVIDER ===== -->
<div class="wave-divider" style="background: linear-gradient(135deg, #023e8a, #0077b6);">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 80">
        <path fill="#031d3a" fill-opacity="1" d="M0,64L80,58.7C160,53,320,43,480,42.7C640,43,800,53,960,58.7C1120,64,1280,64,1360,64L1440,64L1440,80L1360,80C1280,80,1120,80,960,80C800,80,640,80,480,80C320,80,160,80,80,80L0,80Z"></path>
    </svg>
</div>

<!-- ===== FOOTER ===== -->
<footer class="footer" id="contact">
    <div class="container">
        <div class="row g-5">
            <!-- Brand Column -->
            <div class="col-lg-4">
                <div class="footer-logo">
                    <img src="logo.png" alt="Pokhara Metropolitan Logo">
                </div>
                <h5>Pokhara Metro wiis</h5>
                <p class="small" style="line-height: 1.8;" data-i18n="footer_vision">
                    Building a Cleaner, Stronger Nation through responsible waste management and community participation.
                </p>
                <div class="footer-social mt-3">
                    <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" title="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" title="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" title="Email"><i class="bi bi-envelope-fill"></i></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-4">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="bi bi-chevron-right me-1"></i>Home</a></li>
                    <li><a href="login.php"><i class="bi bi-chevron-right me-1"></i>Login</a></li>
                    <li><a href="register.php"><i class="bi bi-chevron-right me-1"></i>Register</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-4">
                <h5>Contact</h5>
                <ul class="footer-links">
                    <li><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Singha Durbar, Kathmandu, Nepal</li>
                    <li><i class="bi bi-telephone-fill me-2 text-danger"></i>+977-1-4200000</li>
                    <li><i class="bi bi-envelope-fill me-2 text-danger"></i>info@wiis.gov.np</li>
                </ul>
            </div>

            <!-- System Vision -->
            <div class="col-lg-3 col-md-4">
                <h5>System Vision</h5>
                <p class="small" style="line-height: 1.8;">
                    To create a zero-waste Nepal by 2030 through technology-driven waste management and community-funded infrastructure development.
                </p>
                <div class="mt-3">
                    <span class="badge-gov">
                        <i class="bi bi-lock-fill me-1"></i> Secure Government Portal
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date("Y"); ?> Waste to Infrastructure Management System. Pokhara Metropolitan City.
                <span data-i18n="footer_rights">All Rights Reserved.</span>
            </p>
        </div>
    </div>
</footer>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/main.js"></script>
<script>
    // Hide preloader after page loads
    window.addEventListener('load', () => {
        const preloader = document.getElementById('preloader');
        preloader.style.opacity = '0';
        setTimeout(() => preloader.style.display = 'none', 500);
    });

    // Sticky navbar shadow on scroll
    window.addEventListener('scroll', () => {
        const navbar = document.querySelector('.navbar-custom');
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
            } else {
                navbar.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.1)';
            }
        }
    });
</script>
</body>
</html>
