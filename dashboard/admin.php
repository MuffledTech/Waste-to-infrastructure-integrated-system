<?php
// dashboard/admin.php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch Stats (unchanged)
$stats = [];
$stats['users']    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='citizen'")->fetchColumn();
$stats['waste']    = $pdo->query("SELECT SUM(weight) FROM waste_reports WHERE status='approved'")->fetchColumn() ?: 0;
$stats['projects'] = $pdo->query("SELECT COUNT(*) FROM infrastructure_projects")->fetchColumn();
$stats['funds']    = $pdo->query("SELECT SUM(amount_allocated) FROM fund_allocations")->fetchColumn() ?: 0;

// Fetch Pending Waste Approvals (Collected but not Approved) — EXISTING FEATURE, UNCHANGED
$approvals_stmt = $pdo->query("SELECT r.*, u.name as collector_name FROM waste_reports r JOIN pickups p ON r.id = p.report_id JOIN users u ON p.collector_id = u.id WHERE r.status = 'collected'");
$approvals = $approvals_stmt->fetchAll();

// ===== NEW: Fetch Pending User Registrations =====
$pending_users_stmt = $pdo->query("SELECT id, name, email, municipality, home_number, citizenship_number, created_at FROM users WHERE role = 'citizen' AND status = 'pending' ORDER BY created_at DESC");
$pending_users = $pending_users_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Pokhara Metropolitan Waste System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; }
        img { max-width: 100%; height: auto; }
        /* Admin Navbar */
        .admin-navbar { background: linear-gradient(135deg, #023e8a 0%, #0077b6 55%, #0096c7 100%) !important; padding: 12px 0; box-shadow: 0 2px 12px rgba(2,62,138,0.25); }
        .admin-navbar .navbar-brand, .admin-navbar .navbar-text { color: #fff !important; }
        /* Admin Stat Cards */
        .admin-stat-card { background: #fff; border-radius: 16px; padding: 24px 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.07); border-top: 4px solid #0096c7; transition: transform 0.3s; height: 100%; }
        .admin-stat-card:hover { transform: translateY(-5px); }
        /* Section headers */
        .admin-section-header { background: linear-gradient(135deg, #023e8a, #0077b6); color: #fff; padding: 14px 20px; font-weight: 700; font-size: 0.95rem; border-radius: 8px 8px 0 0; }
        .admin-section-header-green { background: linear-gradient(135deg, #0077b6, #0096c7); color: #fff; padding: 14px 20px; font-weight: 700; font-size: 0.95rem; border-radius: 8px 8px 0 0; }
        .admin-section-header-warning { background: linear-gradient(135deg, #023e8a 30%, #0055a5 100%); color: #fff; padding: 14px 20px; font-weight: 700; font-size: 0.95rem; border-radius: 8px 8px 0 0; }
        /* Buttons */
        .btn-primary-custom { background: linear-gradient(135deg, #023e8a, #0077b6); color: #fff !important; border: none; border-radius: 8px; padding: 8px 20px; font-weight: 600; transition: all 0.3s; cursor: pointer; }
        .btn-primary-custom:hover { background: linear-gradient(135deg, #0077b6, #0096c7); transform: translateY(-1px); }
    </style>
</head>
<body style="background: #f4f9fc;">

    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar mb-0">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <img src="../logo.png" alt="Logo" style="height:40px;">
                <div>
                    <div style="font-size:0.95rem; font-weight:700; line-height:1.2;">Admin Control Center</div>
                    <div style="font-size:0.7rem; opacity:0.8;">Pokhara Metropolitan Waste System</div>
                </div>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-text text-white-50 small">
                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['name']); ?>
                </span>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 pb-5">

        <!-- Stats Row -->
        <div class="row text-center mb-4 g-3">
            <div class="col-md-3">
                <div class="admin-stat-card" style="border-top-color: #0096c7;">
                    <div style="font-size:2rem; color:#0096c7;"><i class="bi bi-people-fill"></i></div>
                    <h3 style="color:#023e8a;" class="mt-2"><?php echo $stats['users']; ?></h3>
                    <p class="text-muted mb-0 small">Total Citizens</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-stat-card" style="border-top-color: #0077b6;">
                    <div style="font-size:2rem; color:#0077b6;"><i class="bi bi-trash3-fill"></i></div>
                    <h3 style="color:#023e8a;" class="mt-2"><?php echo number_format($stats['waste'], 2); ?> kg</h3>
                    <p class="text-muted mb-0 small">Recycled Waste</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-stat-card" style="border-top-color: #0096c7;">
                    <div style="font-size:2rem; color:#0096c7;"><i class="bi bi-building-fill-check"></i></div>
                    <h3 style="color:#023e8a;" class="mt-2"><?php echo $stats['projects']; ?></h3>
                    <p class="text-muted mb-0 small">Active Projects</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="admin-stat-card" style="border-top-color: #0077b6;">
                    <div style="font-size:2rem; color:#0077b6;"><i class="bi bi-cash-stack"></i></div>
                    <h3 style="color:#023e8a;" class="mt-2">Rs <?php echo number_format($stats['funds']); ?></h3>
                    <p class="text-muted mb-0 small">Funds Allocated</p>
                </div>
            </div>
        </div>

        <!-- ===== NEW: Pending User Registrations Section ===== -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="admin-section-header-warning">
                <i class="bi bi-person-check me-2"></i>
                Pending Citizen Registrations
                <?php if (count($pending_users) > 0): ?>
                    <span class="badge bg-white text-dark ms-2"><?php echo count($pending_users); ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($pending_users)): ?>
                    <div class="text-center py-3 text-muted">
                        <i class="bi bi-check-circle text-success fs-3 d-block mb-2"></i>
                        No pending registrations. All citizens have been reviewed.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Municipality</th>
                                    <th>Home No.</th>
                                    <th>Citizenship No.</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_users as $pu): ?>
                                    <tr>
                                        <td><?php echo (int)$pu['id']; ?></td>
                                        <td>
                                            <i class="bi bi-person-circle me-1 text-primary"></i>
                                            <?php echo htmlspecialchars($pu['name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($pu['email']); ?></td>
                                        <td><?php echo htmlspecialchars($pu['municipality'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($pu['home_number'] ?: '—'); ?></td>
                                        <td><?php echo htmlspecialchars($pu['citizenship_number'] ?: '—'); ?></td>
                                        <td class="text-muted small"><?php echo date('d M Y', strtotime($pu['created_at'])); ?></td>
                                        <td>
                                            <form action="../api/approve_user.php" method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$pu['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="bi bi-check-lg me-1"></i>Approve
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- ===== END: Pending Registrations ===== -->

        <div class="row g-4">
            <!-- Waste Approvals Section (EXISTING — UNCHANGED) -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="admin-section-header-green">
                        <i class="bi bi-clipboard-check me-2"></i>Pending Waste Approvals (Verified Collections)
                    </div>
                    <div class="card-body">
                        <?php if (empty($approvals)): ?>
                            <p class="text-muted">No pending approvals.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr><th>Type</th><th>Weight</th><th>Collector</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approvals as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['waste_type']); ?></td>
                                                <td><?php echo $item['weight']; ?> kg</td>
                                                <td><?php echo htmlspecialchars($item['collector_name']); ?></td>
                                                <td>
                                                    <form action="../api/approve_report.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="report_id" value="<?php echo $item['id']; ?>">
                                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                            <i class="bi bi-check me-1"></i>Approve
                                                        </button>
                                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-x me-1"></i>Reject
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Create Project Section (EXISTING — UNCHANGED) -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="admin-section-header">
                        <i class="bi bi-building me-2"></i>Create Infrastructure Project
                    </div>
                    <div class="card-body">
                        <form action="../api/create_project.php" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <input type="text" name="project_name" class="form-control" placeholder="Project Name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <input type="text" name="municipality" class="form-control" placeholder="Municipality" required>
                                </div>
                                <div class="col-12 mb-3">
                                    <textarea name="description" class="form-control" placeholder="Project Description" rows="2"></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i>Create Project
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Analytics Chart (EXISTING — UNCHANGED) -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="admin-section-header">
                        <i class="bi bi-bar-chart me-2"></i>Impact Analytics
                    </div>
                    <div class="card-body">
                        <canvas id="impactChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('impactChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Waste Collected (kg)', 'Funds Allocated (Rs)'],
                    datasets: [{
                        data: [<?php echo $stats['waste']; ?>, <?php echo $stats['funds']; ?>],
                        backgroundColor: ['#0077b6', '#0096c7']
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        });
    </script>
</body>
</html>
