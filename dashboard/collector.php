<?php
// dashboard/collector.php
// BACKEND LOGIC UNTOUCHED — only UI enhanced
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'collector') {
    header("Location: ../login.php");
    exit;
}

$collector_id = $_SESSION['user_id'];

// Fetch pending reports (ORIGINAL QUERY — UNTOUCHED)
$pending_stmt = $pdo->query("SELECT r.*, u.name as citizen_name FROM waste_reports r JOIN users u ON r.citizen_id = u.id WHERE r.status = 'pending' ORDER BY r.created_at ASC");
$pending_reports = $pending_stmt->fetchAll();

// Fetch my collections (ORIGINAL QUERY — UNTOUCHED)
$my_collections_stmt = $pdo->prepare("SELECT p.*, r.waste_type, r.created_at as report_date FROM pickups p JOIN waste_reports r ON p.report_id = r.id WHERE p.collector_id = ? ORDER BY p.collected_at DESC");
$my_collections_stmt->execute([$collector_id]);
$my_collections = $my_collections_stmt->fetchAll();

// Stats
$total_pending = count($pending_reports);
$total_collected = count($my_collections);
$today_collected = count(array_filter($my_collections, fn($c) => date('Y-m-d', strtotime($c['collected_at'])) == date('Y-m-d')));

// Build map pins JSON for all pending reports with coordinates
$map_pins = [];
$heatmap_points = [];
foreach ($pending_reports as $r) {
    $lat = !empty($r['latitude'])  ? (float)$r['latitude']  : null;
    $lng = !empty($r['longitude']) ? (float)$r['longitude'] : null;
    // If no GPS, place randomly near Pokhara for demo visibility
    if (!$lat) { $lat = 28.2096 + (mt_rand(-50, 50) / 1000); $lng = 83.9856 + (mt_rand(-50, 50) / 1000); }
    $map_pins[] = [
        'id'           => $r['id'],
        'lat'          => $lat,
        'lng'          => $lng,
        'type'         => htmlspecialchars($r['waste_type']),
        'citizen'      => htmlspecialchars($r['citizen_name']),
        'date'         => date('M d, Y', strtotime($r['created_at'])),
        'description'  => htmlspecialchars($r['description'] ?? ''),
        'image'        => !empty($r['image']) ? '../' . $r['image'] : '',
    ];
    $heatmap_points[] = [$lat, $lng, 1.0];
}
$map_pins_json    = json_encode($map_pins);
$heatmap_json     = json_encode($heatmap_points);

// Waste type filter options
$waste_types = array_unique(array_column($pending_reports, 'waste_type'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collector Dashboard — wiis Nepal</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f9fc; }

        /* ===== SIDEBAR (self-contained) ===== */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background: linear-gradient(180deg, #023e8a 0%, #01294f 100%);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            transition: width 0.3s;
            overflow: hidden;
        }
        .sidebar.collapsed { width: 70px; }
        .sidebar-header {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-logo { width: 36px; flex-shrink: 0; }
        .sidebar-title { color: #fff; font-weight: 700; font-size: 0.95rem; white-space: nowrap; overflow: hidden; }
        .sidebar-nav { list-style: none; padding: 15px 0; margin: 0; }
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            white-space: nowrap;
        }
        .sidebar-nav li a:hover,
        .sidebar-nav li a.active { color: #fff; background: rgba(255,255,255,0.1); border-left-color: #90e0ef; }
        .sidebar-nav li a i { font-size: 1.1rem; min-width: 20px; text-align: center; }
        .sidebar-nav .nav-label {
            color: rgba(255,255,255,0.4);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 15px 20px 5px;
            display: block;
        }
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .nav-text { display: none; }

        /* ===== MAIN WRAPPER ===== */
        .main-wrapper { margin-left: 250px; transition: margin-left 0.3s; min-height: 100vh; }
        .main-wrapper.expanded { margin-left: 70px; }

        /* ===== TOP NAVBAR ===== */
        .top-navbar {
            background: #fff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(2,62,138,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .toggle-btn { background: none; border: none; font-size: 1.4rem; color: #0077b6; cursor: pointer; }

        /* ===== STAT CARDS ===== */
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border-left: 4px solid #0096c7;
            transition: transform 0.2s;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-card .icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #023e8a; }
        .stat-card .label { font-size: 0.85rem; color: #5a7184; }

        /* ===== MAP ===== */
        #collectorMap { height: 520px; border-radius: 16px; z-index: 1; }
        .map-legend { background: rgba(2,62,138,0.88); color: #fff; padding: 10px 14px; border-radius: 10px; font-size: 0.8rem; }
        .map-legend .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; }
        .heatmap-legend { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; }
        .heatmap-gradient { width: 80px; height: 10px; border-radius: 5px; background: linear-gradient(to right, #0096c7, #0077b6, #023e8a); }

        /* ===== FILTER BAR ===== */
        .filter-bar { background: #fff; border-radius: 12px; padding: 14px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .filter-select { border-radius: 8px; border: 1.5px solid #cce3f0; font-size: 0.85rem; padding: 6px 12px; }
        .filter-select:focus { border-color: #0077b6; box-shadow: 0 0 0 3px rgba(0,119,182,0.1); outline: none; }

        /* ===== PICKUP CARDS ===== */
        .pickup-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: all 0.3s; border: 1px solid #f4f9fc; }
        .pickup-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .pickup-card img { width: 100%; height: 160px; object-fit: cover; }
        .priority-high   { border-top: 4px solid #ef4444; }
        .priority-medium { border-top: 4px solid #f59e0b; }
        .priority-low    { border-top: 4px solid #0096c7; }

        /* ===== BUTTONS ===== */
        .btn-primary-custom {
            background: linear-gradient(135deg, #023e8a, #0077b6);
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #0077b6, #0096c7);
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(0,119,182,0.35);
        }

        /* ===== STATUS BADGES ===== */
        .badge-pending  { background: #fff3cd; color: #6d4c08; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; display: inline-block; }
        .badge-approved { background: #cce8fa; color: #023e8a; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; display: inline-block; }

        /* ===== BOX SIZING (safety) ===== */
        *, *::before, *::after { box-sizing: border-box; }
        img { max-width: 100%; height: auto; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-text, .sidebar .sidebar-title, .sidebar .nav-label { display: none; }
            .main-wrapper { margin-left: 70px; }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../logo.png" class="sidebar-logo" alt="Nepal">
        <span class="sidebar-title">WIIS</span>
    </div>
    <ul class="sidebar-nav">
        <li><span class="nav-label">Collector</span></li>
        <li><a href="#" class="active" data-tab="overview"><i class="bi bi-speedometer2"></i><span class="nav-text">Dashboard</span></a></li>
        <li><a href="#" data-tab="missionmap"><i class="bi bi-map-fill"></i><span class="nav-text">Mission Map</span></a></li>
        <li><a href="#" data-tab="pickups"><i class="bi bi-truck-front-fill"></i><span class="nav-text">Available Pickups</span></a></li>
        <li><a href="#" data-tab="history"><i class="bi bi-clock-history"></i><span class="nav-text">My History</span></a></li>
        <li><span class="nav-label">Account</span></li>
        <li><a href="../logout.php"><i class="bi bi-box-arrow-right"></i><span class="nav-text">Logout</span></a></li>
    </ul>
</div>

<!-- ===== MAIN WRAPPER ===== -->
<div class="main-wrapper" id="mainWrapper">

    <!-- TOP NAVBAR -->
    <div class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="toggle-btn" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div>
                <div class="fw-700 text-dark" style="font-size:1rem;">Collector Portal</div>
                <div class="text-muted" style="font-size:0.78rem;">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary lang-btn" data-lang="en">EN</button>
                <button class="btn btn-outline-secondary lang-btn" data-lang="np">ने</button>
            </div>
            <span class="badge rounded-pill px-3 py-2" style="background:linear-gradient(135deg,#023e8a,#0096c7); color:#fff;">
                <i class="bi bi-exclamation-circle me-1"></i><?php echo $total_pending; ?> Pending
            </span>
            <a href="../logout.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>

    <div class="p-4">

        <!-- ===== OVERVIEW TAB ===== -->
        <div id="tab-overview" class="tab-section">
            <div class="mb-4">
                <h4 class="fw-700 text-dark mb-1">Collector Dashboard</h4>
                <p class="text-muted small">Manage waste pickups and track your collections.</p>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-exclamation-circle-fill"></i></div>
                            <div>
                                <div class="value"><?php echo $total_pending; ?></div>
                                <div class="label">Pending Pickups</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-check-circle-fill"></i></div>
                            <div>
                                <div class="value"><?php echo $today_collected; ?></div>
                                <div class="label">Collected Today</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-truck-front-fill"></i></div>
                            <div>
                                <div class="value"><?php echo $total_collected; ?></div>
                                <div class="label">Total Collections</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-3">
                <div class="col-md-5" data-aos="fade-up">
                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                        <h6 class="fw-700 mb-3"><i class="bi bi-lightning-fill text-warning me-2"></i>Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger" onclick="switchTab('missionmap')">
                                <i class="bi bi-map me-2"></i>Open Mission Map
                            </button>
                            <button class="btn btn-outline-primary" onclick="switchTab('pickups')">
                                <i class="bi bi-truck-front me-2"></i>View Available Pickups
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-7" data-aos="fade-up" data-aos-delay="100">
                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                        <h6 class="fw-700 mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Collections</h6>
                        <?php if (empty($my_collections)): ?>
                            <p class="text-muted small">No collections yet. Start collecting waste!</p>
                        <?php else: ?>
                            <?php foreach (array_slice($my_collections, 0, 4) as $c): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-600 small"><?php echo htmlspecialchars($c['waste_type']); ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;"><?php echo date('M d, Y H:i', strtotime($c['collected_at'])); ?></div>
                                    </div>
                                    <span class="badge bg-success text-white">Collected</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MISSION MAP TAB ===== -->
        <div id="tab-missionmap" class="tab-section d-none">
            <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="fw-700 text-dark mb-1"><i class="bi bi-map-fill text-danger me-2"></i>Mission Map</h4>
                    <p class="text-muted small">View all pending waste reports. Click a pin to collect.</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-sm btn-outline-secondary" id="toggleHeatmap">
                        <i class="bi bi-fire me-1"></i>Toggle Heatmap
                    </button>
                    <button class="btn btn-sm btn-outline-primary" id="togglePins">
                        <i class="bi bi-geo-alt me-1"></i>Toggle Pins
                    </button>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar mb-3 d-flex gap-3 align-items-center flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <label class="small fw-600 text-nowrap">Filter by Type:</label>
                    <select class="filter-select" id="typeFilter" onchange="filterMapPins()">
                        <option value="all">All Types</option>
                        <?php foreach ($waste_types as $wt): ?>
                            <option value="<?php echo $wt; ?>"><?php echo $wt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="map-legend">
                        <div class="heatmap-legend">
                            <span>Low</span><div class="heatmap-gradient"></div><span>High Density</span>
                        </div>
                    </div>
                    <div class="small text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?php echo $total_pending; ?> pins</div>
                </div>
            </div>

            <div class="bg-white rounded-4 p-3 shadow-sm">
                <div id="collectorMap"></div>
            </div>
        </div>

        <!-- ===== AVAILABLE PICKUPS TAB ===== -->
        <div id="tab-pickups" class="tab-section d-none">
            <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="fw-700 text-dark mb-1"><i class="bi bi-truck-front-fill text-primary me-2"></i>Available Pickups</h4>
                    <p class="text-muted small"><?php echo $total_pending; ?> pending waste reports awaiting collection.</p>
                </div>
                <select class="filter-select" id="cardTypeFilter" onchange="filterCards()">
                    <option value="all">All Types</option>
                    <?php foreach ($waste_types as $wt): ?>
                        <option value="<?php echo $wt; ?>"><?php echo $wt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (empty($pending_reports)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <h5 class="mt-3 fw-700">All Clear!</h5>
                    <p class="text-muted">No pending waste reports. Great work!</p>
                </div>
            <?php else: ?>
                <div class="row g-4" id="pickupGrid">
                    <?php foreach ($pending_reports as $i => $report):
                        // Priority based on age: >3 days = high, >1 day = medium, else low
                        $age_days = (time() - strtotime($report['created_at'])) / 86400;
                        $priority = $age_days > 3 ? 'high' : ($age_days > 1 ? 'medium' : 'low');
                        $priority_label = ['high'=>'High Priority','medium'=>'Medium','low'=>'New'];
                        $priority_color = ['high'=>'danger','medium'=>'warning','low'=>'success'];
                    ?>
                        <div class="col-md-4 pickup-item" data-type="<?php echo $report['waste_type']; ?>" data-aos="fade-up" data-aos-delay="<?php echo ($i % 3) * 80; ?>">
                            <div class="pickup-card priority-<?php echo $priority; ?>">
                                <img src="../<?php echo !empty($report['image']) ? $report['image'] : 'assets/img/default_waste.png'; ?>"
                                     alt="Waste" onerror="this.src='https://placehold.co/400x160/f1f5f9/94a3b8?text=No+Image'">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-700 mb-0"><?php echo htmlspecialchars($report['waste_type']); ?></h6>
                                        <span class="badge bg-<?php echo $priority_color[$priority]; ?> text-white"><?php echo $priority_label[$priority]; ?></span>
                                    </div>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($report['description'] ?: 'No description provided.'); ?></p>
                                    <div class="small text-muted mb-1"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($report['citizen_name']); ?></div>
                                    <div class="small text-muted mb-3"><i class="bi bi-calendar me-1"></i><?php echo date('M d, Y', strtotime($report['created_at'])); ?></div>
                                    <?php if (!empty($report['latitude'])): ?>
                                        <div class="small text-success mb-3"><i class="bi bi-geo-alt-fill me-1"></i><?php echo round($report['latitude'],4); ?>, <?php echo round($report['longitude'],4); ?></div>
                                    <?php endif; ?>
                                    <button class="btn btn-primary-custom w-100 btn-sm" onclick="openCollectionModal(<?php echo $report['id']; ?>)">
                                        <i class="bi bi-check-circle me-2"></i>Mark as Collected
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== HISTORY TAB ===== -->
        <div id="tab-history" class="tab-section d-none">
            <div class="mb-4">
                <h4 class="fw-700 text-dark mb-1"><i class="bi bi-clock-history text-info me-2"></i>My Collection History</h4>
                <p class="text-muted small"><?php echo $total_collected; ?> total collections.</p>
            </div>
            <div class="bg-white rounded-4 shadow-sm overflow-hidden" data-aos="fade-up">
                <?php if (empty($my_collections)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-truck-front fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No collections yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background:#eaf4fb;">
                                <tr>
                                    <th class="ps-4">Report Date</th>
                                    <th>Waste Type</th>
                                    <th>Collected At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_collections as $collection): ?>
                                    <tr>
                                        <td class="ps-4"><?php echo date('M d, Y', strtotime($collection['report_date'])); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($collection['waste_type']); ?></span></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($collection['collected_at'])); ?></td>
                                        <td><span class="badge-approved">Collected</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /p-4 -->
</div><!-- /main-wrapper -->

<!-- ===== COLLECTION MODAL (ORIGINAL FORM — UNTOUCHED) ===== -->
<div class="modal fade" id="collectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <form action="../api/update_status.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="report_id" id="modal_report_id">
                <input type="hidden" name="action" value="collect">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-700"><i class="bi bi-check-circle-fill text-success me-2"></i>Confirm Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-600">Waste Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" class="form-control" required placeholder="e.g. 5.5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-600">Verification Image <span class="text-muted fw-400">(Optional)</span></label>
                        <input type="file" name="verification_image" class="form-control" accept="image/*">
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>Marking as collected will award points to the citizen automatically.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle me-2"></i>Mark as Collected</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Leaflet.heat for heatmap -->
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="../assets/js/main.js"></script>
<script>
AOS.init({ duration: 700, once: true });

// ===== SIDEBAR TOGGLE =====
const sidebar = document.getElementById('sidebar');
const mainWrapper = document.getElementById('mainWrapper');
document.getElementById('sidebarToggle').addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    mainWrapper.classList.toggle('expanded');
});

// ===== TAB NAVIGATION =====
function switchTab(tabName) {
    document.querySelectorAll('.tab-section').forEach(t => t.classList.add('d-none'));
    document.getElementById('tab-' + tabName).classList.remove('d-none');
    document.querySelectorAll('.sidebar-nav a[data-tab]').forEach(a => a.classList.remove('active'));
    const activeLink = document.querySelector(`.sidebar-nav a[data-tab="${tabName}"]`);
    if (activeLink) activeLink.classList.add('active');
    if (tabName === 'missionmap') setTimeout(initCollectorMap, 100);
}
document.querySelectorAll('.sidebar-nav a[data-tab]').forEach(link => {
    link.addEventListener('click', e => { e.preventDefault(); switchTab(link.dataset.tab); });
});

// ===== COLLECTION MODAL =====
function openCollectionModal(id) {
    document.getElementById('modal_report_id').value = id;
    new bootstrap.Modal(document.getElementById('collectionModal')).show();
}

// ===== COLLECTOR MAP + HEATMAP =====
let collectorMap = null;
let heatLayer = null;
let pinLayer = null;
let pinsVisible = true;
let heatVisible = true;

const POKHARA = [28.2096, 83.9856];
const mapPins = <?php echo $map_pins_json; ?>;
const heatPoints = <?php echo $heatmap_json; ?>;

const typeColors = {
    'Plastic':   '#3b82f6',
    'Organic':   '#22c55e',
    'Metal':     '#f59e0b',
    'Paper':     '#8b5cf6',
    'Hazardous': '#ef4444',
};

function initCollectorMap() {
    if (collectorMap) { collectorMap.invalidateSize(); return; }

    collectorMap = L.map('collectorMap').setView(POKHARA, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(collectorMap);

    // ===== HEATMAP LAYER =====
    if (heatPoints.length > 0) {
        heatLayer = L.heatLayer(heatPoints, {
            radius: 35, blur: 25, maxZoom: 17,
            gradient: { 0.2: '#22c55e', 0.5: '#eab308', 0.8: '#f97316', 1.0: '#ef4444' }
        }).addTo(collectorMap);
    }

    // ===== PIN MARKERS =====
    pinLayer = L.layerGroup().addTo(collectorMap);
    renderPins(mapPins);
}

function renderPins(pins) {
    pinLayer.clearLayers();
    pins.forEach(pin => {
        const color = typeColors[pin.type] || '#9b1c1c';
        const icon = L.divIcon({
            className: '',
            html: `<div style="
                width: 32px; height: 32px;
                background: ${color};
                border: 3px solid #fff;
                border-radius: 50% 50% 50% 0;
                transform: rotate(-45deg);
                box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            "></div>`,
            iconSize: [32, 32], iconAnchor: [16, 32]
        });

        const marker = L.marker([pin.lat, pin.lng], { icon }).addTo(pinLayer);

        let popupHtml = `<div style="min-width:200px;font-family:Poppins,sans-serif;padding:4px;">
            <div style="font-weight:700;font-size:1rem;margin-bottom:4px;">${pin.type}</div>
            <div style="font-size:0.8rem;color:#64748b;margin-bottom:2px;"><i class="bi bi-person"></i> ${pin.citizen}</div>
            <div style="font-size:0.8rem;color:#64748b;margin-bottom:8px;"><i class="bi bi-calendar"></i> ${pin.date}</div>`;
        if (pin.description) popupHtml += `<div style="font-size:0.8rem;margin-bottom:8px;">${pin.description}</div>`;
        if (pin.image) popupHtml += `<img src="${pin.image}" style="width:100%;border-radius:8px;max-height:100px;object-fit:cover;margin-bottom:8px;" onerror="this.style.display='none'">`;
        popupHtml += `<button onclick="openCollectionModal(${pin.id})" style="
            background:#9b1c1c;color:#fff;border:none;border-radius:8px;
            padding:8px 16px;width:100%;font-weight:600;cursor:pointer;font-size:0.85rem;">
            ✓ Mark as Collected
        </button></div>`;

        marker.bindPopup(popupHtml, { maxWidth: 240 });
    });
}

// ===== FILTER MAP PINS =====
function filterMapPins() {
    const type = document.getElementById('typeFilter').value;
    const filtered = type === 'all' ? mapPins : mapPins.filter(p => p.type === type);
    renderPins(filtered);
}

// ===== TOGGLE HEATMAP =====
document.getElementById('toggleHeatmap').addEventListener('click', () => {
    if (!collectorMap) return;
    if (heatVisible && heatLayer) { collectorMap.removeLayer(heatLayer); heatVisible = false; }
    else if (heatLayer) { heatLayer.addTo(collectorMap); heatVisible = true; }
});

// ===== TOGGLE PINS =====
document.getElementById('togglePins').addEventListener('click', () => {
    if (!collectorMap) return;
    if (pinsVisible) { collectorMap.removeLayer(pinLayer); pinsVisible = false; }
    else { pinLayer.addTo(collectorMap); pinsVisible = true; }
});

// ===== FILTER PICKUP CARDS =====
function filterCards() {
    const type = document.getElementById('cardTypeFilter').value;
    document.querySelectorAll('.pickup-item').forEach(item => {
        item.style.display = (type === 'all' || item.dataset.type === type) ? '' : 'none';
    });
}
</script>
</body>
</html>
