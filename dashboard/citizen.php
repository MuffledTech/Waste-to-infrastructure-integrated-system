<?php
// dashboard/citizen.php
// BACKEND LOGIC UNTOUCHED — only UI enhanced
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'citizen') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user stats (ORIGINAL QUERIES — UNTOUCHED)
$points_stmt = $pdo->prepare("SELECT points FROM reward_points WHERE user_id = ?");
$points_stmt->execute([$user_id]);
$points = $points_stmt->fetchColumn() ?: 0;

$reports_stmt = $pdo->prepare("SELECT * FROM waste_reports WHERE citizen_id = ? ORDER BY created_at DESC");
$reports_stmt->execute([$user_id]);
$reports = $reports_stmt->fetchAll();

// Fetch projects (ORIGINAL QUERY — UNTOUCHED)
$projects_stmt = $pdo->query("SELECT * FROM infrastructure_projects ORDER BY created_at DESC");
$projects = $projects_stmt->fetchAll();

// Count stats
$total_reports = count($reports);
$approved_reports = count(array_filter($reports, fn($r) => $r['status'] == 'approved'));
$pending_reports_count = count(array_filter($reports, fn($r) => $r['status'] == 'pending'));

// Build map pins JSON from reports that have coordinates
$map_pins = [];
foreach ($reports as $r) {
    if (!empty($r['latitude']) && !empty($r['longitude'])) {
        $map_pins[] = [
            'lat'       => (float)$r['latitude'],
            'lng'       => (float)$r['longitude'],
            'type'      => htmlspecialchars($r['waste_type']),
            'status'    => $r['status'],
            'date'      => date('M d, Y', strtotime($r['created_at'])),
            'image'     => !empty($r['image']) ? '../' . $r['image'] : '',
        ];
    }
}
$map_pins_json = json_encode($map_pins);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Dashboard — wiis Nepal</title>

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

        /* ===== SIDEBAR (self-contained — works without style.css) ===== */
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
        .sidebar-nav li a.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left-color: #90e0ef;
        }
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
        .points-badge {
            background: linear-gradient(135deg, #023e8a, #0096c7);
            color: #fff;
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }

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

        /* ===== TABS ===== */
        .nav-tabs-custom { border-bottom: 2px solid #cce3f0; }
        .nav-tabs-custom .nav-link {
            border: none;
            color: #5a7184;
            font-weight: 500;
            padding: 12px 20px;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
        }
        .nav-tabs-custom .nav-link.active { color: #0077b6; border-bottom-color: #0077b6; background: none; }
        .nav-tabs-custom .nav-link:hover { color: #0077b6; }

        /* ===== MAP ===== */
        #citizenMap { height: 480px; border-radius: 16px; z-index: 1; }
        .map-instructions { background: rgba(2,62,138,0.88); color: #fff; padding: 10px 16px; border-radius: 8px; font-size: 0.85rem; }
        .map-legend span { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 5px; }

        /* ===== MARKETPLACE ===== */
        .product-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: all 0.3s; border: 1px solid #f4f9fc; }
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px rgba(0,0,0,0.12); }
        .product-card img { width: 100%; height: 180px; object-fit: cover; }
        .product-card .card-body { padding: 16px; }
        .product-card .price-badge { background: linear-gradient(135deg, #023e8a, #0077b6); color: #fff; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
        .category-pill { cursor: pointer; padding: 6px 16px; border-radius: 50px; border: 1.5px solid #cce3f0; font-size: 0.85rem; font-weight: 500; transition: all 0.2s; background: #fff; color: #5a7184; }
        .category-pill.active, .category-pill:hover { background: #0077b6; color: #fff; border-color: #0077b6; }
        .search-bar { border-radius: 50px; border: 1.5px solid #cce3f0; padding: 10px 20px; font-size: 0.9rem; }
        .search-bar:focus { border-color: #0077b6; box-shadow: 0 0 0 3px rgba(0,119,182,0.1); outline: none; }

        /* ===== QUICK ACTION BUTTONS ===== */
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
        .badge-pending   { background: #fff3cd; color: #6d4c08; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; display: inline-block; }
        .badge-approved  { background: #cce8fa; color: #023e8a; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; display: inline-block; }
        .badge-collected { background: #d0eaf9; color: #0077b6; padding: 4px 10px; border-radius: 50px; font-size: 0.78rem; font-weight: 600; display: inline-block; }

        /* ===== REPORT MODAL MAP ===== */
        #reportMiniMap { height: 220px; border-radius: 10px; margin-bottom: 10px; }

        /* ===== FILTER BAR ===== */
        .filter-bar { background: #fff; border-radius: 12px; padding: 14px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .filter-select { border-radius: 8px; border: 1.5px solid #cce3f0; font-size: 0.85rem; padding: 6px 12px; }
        .filter-select:focus { border-color: #0077b6; box-shadow: 0 0 0 3px rgba(0,119,182,0.1); outline: none; }

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
        <span class="sidebar-title">wiis Nepal</span>
    </div>
    <ul class="sidebar-nav">
        <li><span class="nav-label">Main</span></li>
        <li><a href="#" class="active" data-tab="overview"><i class="bi bi-speedometer2"></i><span class="nav-text">Dashboard</span></a></li>
        <li><a href="#" data-tab="map"><i class="bi bi-geo-alt-fill"></i><span class="nav-text">Report on Map</span></a></li>
        <li><a href="#" data-tab="marketplace"><i class="bi bi-shop"></i><span class="nav-text">Marketplace</span></a></li>
        <li><a href="#" data-tab="reports"><i class="bi bi-list-check"></i><span class="nav-text">My Reports</span></a></li>
        <li><a href="#" data-tab="projects"><i class="bi bi-building"></i><span class="nav-text">Projects</span></a></li>
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
                <div class="fw-700 text-dark" style="font-size:1rem;">Citizen Portal</div>
                <div class="text-muted" style="font-size:0.78rem;">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary lang-btn" data-lang="en">EN</button>
                <button class="btn btn-outline-secondary lang-btn" data-lang="np">ने</button>
            </div>
            <div class="points-badge"><i class="bi bi-trophy-fill me-1"></i><?php echo $points; ?> pts</div>
            <a href="../logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>

    <!-- PAGE CONTENT -->
    <div class="p-4">

        <!-- ===== OVERVIEW TAB ===== -->
        <div id="tab-overview" class="tab-section">
            <div class="mb-4">
                <h4 class="fw-700 text-dark mb-1">My Dashboard</h4>
                <p class="text-muted small">Track your waste reports and rewards.</p>
            </div>

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-trophy-fill"></i></div>
                            <div>
                                <div class="value"><?php echo $points; ?></div>
                                <div class="label">Reward Points</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-camera-fill"></i></div>
                            <div>
                                <div class="value"><?php echo $total_reports; ?></div>
                                <div class="label">Total Reports</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-check-circle-fill"></i></div>
                            <div>
                                <div class="value"><?php echo $approved_reports; ?></div>
                                <div class="label">Approved Reports</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-3">
                <div class="col-md-6" data-aos="fade-up">
                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                        <h6 class="fw-700 mb-3"><i class="bi bi-lightning-fill text-warning me-2"></i>Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">
                                <i class="bi bi-plus-circle me-2"></i>Report New Waste
                            </button>
                            <button class="btn btn-outline-primary" onclick="switchTab('map')">
                                <i class="bi bi-map me-2"></i>Open Map & Pin Location
                            </button>
                            <button class="btn btn-outline-success" onclick="switchTab('marketplace')">
                                <i class="bi bi-shop me-2"></i>Browse Marketplace
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                        <h6 class="fw-700 mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Reports</h6>
                        <?php if (empty($reports)): ?>
                            <p class="text-muted small">No reports yet. Start by reporting waste!</p>
                        <?php else: ?>
                            <?php foreach (array_slice($reports, 0, 3) as $r): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-600 small"><?php echo htmlspecialchars($r['waste_type']); ?></div>
                                        <div class="text-muted" style="font-size:0.75rem;"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                                    </div>
                                    <span class="badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MAP TAB ===== -->
        <div id="tab-map" class="tab-section d-none">
            <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="fw-700 text-dark mb-1"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Report on Map</h4>
                    <p class="text-muted small">Click anywhere on the map to pin a waste location, then submit your report.</p>
                </div>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="bi bi-plus-circle me-2"></i>Submit Report
                </button>
            </div>

            <div class="bg-white rounded-4 p-3 shadow-sm mb-3">
                <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                    <div class="map-instructions">
                        <i class="bi bi-hand-index-thumb me-1"></i> Click on the map to drop a pin. Coordinates will auto-fill in the report form.
                    </div>
                    <div class="d-flex gap-3 ms-auto small text-muted map-legend">
                        <span><span style="background:#f59e0b;"></span>Pending</span>
                        <span><span style="background:#0096c7;"></span>Approved</span>
                        <span><span style="background:#0077b6;"></span>Collected</span>
                    </div>
                </div>
                <div id="citizenMap"></div>
            </div>

            <div class="bg-white rounded-4 p-3 shadow-sm">
                <p class="small text-muted mb-1"><i class="bi bi-info-circle me-1"></i>Selected coordinates:</p>
                <div class="d-flex gap-3">
                    <div class="flex-grow-1">
                        <label class="form-label small fw-600">Latitude</label>
                        <input type="text" id="displayLat" class="form-control form-control-sm" readonly placeholder="Click map to set">
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label small fw-600">Longitude</label>
                        <input type="text" id="displayLng" class="form-control form-control-sm" readonly placeholder="Click map to set">
                    </div>
                    <div class="d-flex align-items-end">
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="bi bi-send me-1"></i>Report Here
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== MARKETPLACE TAB ===== -->
        <div id="tab-marketplace" class="tab-section d-none">
            <div class="mb-4">
                <h4 class="fw-700 text-dark mb-1"><i class="bi bi-shop text-success me-2"></i>Reward Marketplace</h4>
                <p class="text-muted small">Redeem your points for eco-friendly products and building materials.</p>
            </div>

            <!-- Points Banner -->
            <div class="rounded-4 p-4 mb-4 text-white" style="background: linear-gradient(135deg, #023e8a, #0077b6);" data-aos="fade-up">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="small opacity-75">Your Available Balance</div>
                        <div style="font-size:2.5rem; font-weight:800;"><?php echo $points; ?> <span style="font-size:1rem; font-weight:400;">Points</span></div>
                    </div>
                    <div class="text-end">
                        <div class="small opacity-75">Earn more points by</div>
                        <div class="fw-600">Reporting & Verifying Waste</div>
                        <button class="btn btn-light btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#reportModal">
                            <i class="bi bi-plus me-1"></i>Report Waste
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search + Filter -->
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4" data-aos="fade-up">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="marketSearch" class="form-control border-start-0 search-bar" placeholder="Search products..." oninput="filterMarket()">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="category-pill active" data-cat="all" onclick="setCat(this,'all')">All</button>
                            <button class="category-pill" data-cat="bricks" onclick="setCat(this,'bricks')">🧱 Bricks</button>
                            <button class="category-pill" data-cat="plastic" onclick="setCat(this,'plastic')">♻️ Plastic</button>
                            <button class="category-pill" data-cat="metal" onclick="setCat(this,'metal')">🔩 Metal</button>
                            <button class="category-pill" data-cat="eco" onclick="setCat(this,'eco')">🌿 Eco-Goods</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="row g-4" id="productGrid">
                <?php
                // Demo marketplace products (frontend only — no backend change)
                $products = [
                    ['name'=>'Recycled Brick Set','cat'=>'bricks','pts'=>500,'img'=>'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&q=80','desc'=>'Set of 10 eco-bricks made from recycled plastic waste.'],
                    ['name'=>'Plastic Lumber Board','cat'=>'plastic','pts'=>350,'img'=>'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=400&q=80','desc'=>'Durable board made from 100% recycled plastic.'],
                    ['name'=>'Metal Compost Bin','cat'=>'metal','pts'=>600,'img'=>'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=400&q=80','desc'=>'Heavy-duty compost bin for organic waste.'],
                    ['name'=>'Eco Tote Bag','cat'=>'eco','pts'=>150,'img'=>'https://images.unsplash.com/photo-1591195853828-11db59a44f43?w=400&q=80','desc'=>'Reusable jute tote bag — carry green!'],
                    ['name'=>'Bamboo Straw Set','cat'=>'eco','pts'=>100,'img'=>'https://images.unsplash.com/photo-1572635148818-ef6fd45eb394?w=400&q=80','desc'=>'Pack of 12 natural bamboo drinking straws.'],
                    ['name'=>'Recycled Steel Rod','cat'=>'metal','pts'=>800,'img'=>'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?w=400&q=80','desc'=>'Construction-grade recycled steel rod (6mm).'],
                    ['name'=>'Hollow Plastic Block','cat'=>'plastic','pts'=>420,'img'=>'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&q=80','desc'=>'Interlocking hollow block for low-cost construction.'],
                    ['name'=>'Paving Brick (x5)','cat'=>'bricks','pts'=>300,'img'=>'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=400&q=80','desc'=>'5 recycled paving bricks for pathways.'],
                ];
                foreach ($products as $p):
                    $can_afford = $points >= $p['pts'];
                ?>
                <div class="col-md-4 col-lg-3 product-item" data-cat="<?php echo $p['cat']; ?>" data-name="<?php echo strtolower($p['name']); ?>" data-aos="fade-up">
                    <div class="product-card">
                        <img src="<?php echo $p['img']; ?>" alt="<?php echo $p['name']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-700 mb-0" style="font-size:0.9rem;"><?php echo $p['name']; ?></h6>
                                <span class="price-badge"><?php echo $p['pts']; ?> pts</span>
                            </div>
                            <p class="text-muted small mb-3"><?php echo $p['desc']; ?></p>
                            <?php if ($can_afford): ?>
                                <button class="btn btn-danger btn-sm w-100" onclick="redeemProduct('<?php echo $p['name']; ?>', <?php echo $p['pts']; ?>)">
                                    <i class="bi bi-bag-check me-1"></i>Redeem
                                </button>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    <i class="bi bi-lock me-1"></i>Need <?php echo ($p['pts'] - $points); ?> more pts
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="noProducts" class="text-center py-5 d-none">
                <i class="bi bi-search fs-1 text-muted"></i>
                <p class="text-muted mt-2">No products found.</p>
            </div>
        </div>

        <!-- ===== MY REPORTS TAB ===== -->
        <div id="tab-reports" class="tab-section d-none">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-700 text-dark mb-1"><i class="bi bi-list-check text-primary me-2"></i>My Reports</h4>
                    <p class="text-muted small"><?php echo $total_reports; ?> total reports submitted.</p>
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <i class="bi bi-plus-circle me-2"></i>New Report
                </button>
            </div>
            <div class="bg-white rounded-4 shadow-sm overflow-hidden" data-aos="fade-up">
                <?php if (empty($reports)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-camera fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No reports yet. Start reporting waste to earn points!</p>
                        <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#reportModal">Report Waste</button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background:#f8fafc;">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Waste Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td class="ps-4"><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($report['waste_type']); ?></span></td>
                                        <td>
                                            <?php if (!empty($report['latitude'])): ?>
                                                <span class="text-success small"><i class="bi bi-geo-alt-fill me-1"></i><?php echo round($report['latitude'],4); ?>, <?php echo round($report['longitude'],4); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge-<?php echo $report['status']; ?>"><?php echo ucfirst($report['status']); ?></span></td>
                                        <td><?php echo $report['status'] == 'approved' ? '<span class="text-success fw-600">+10 pts</span>' : '<span class="text-muted">—</span>'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== PROJECTS TAB ===== -->
        <div id="tab-projects" class="tab-section d-none">
            <div class="mb-4">
                <h4 class="fw-700 text-dark mb-1"><i class="bi bi-building text-info me-2"></i>Funded Infrastructure Projects</h4>
                <p class="text-muted small">Projects funded by waste collection revenue.</p>
            </div>
            <div class="row g-4">
                <?php if (empty($projects)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-building fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No projects yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="col-md-6" data-aos="fade-up">
                            <div class="bg-white rounded-4 p-4 shadow-sm h-100 border-start border-4" style="border-color: #0096c7 !important;">
                                <h5 class="fw-700"><?php echo htmlspecialchars($project['project_name']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars($project['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fw-600 text-success"><i class="bi bi-currency-dollar me-1"></i><?php echo number_format($project['funded_amount']); ?> Funded</span>
                                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($project['municipality']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /p-4 -->
</div><!-- /main-wrapper -->

<!-- ===== REPORT MODAL ===== -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
            <form action="../api/create_report.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-700"><i class="bi bi-camera-fill text-danger me-2"></i>Report Waste</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-600">Waste Type</label>
                            <select name="waste_type" class="form-select" required>
                                <option value="Plastic">Plastic</option>
                                <option value="Organic">Organic</option>
                                <option value="Metal">Metal</option>
                                <option value="Paper">Paper</option>
                                <option value="Hazardous">Hazardous</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Upload Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Describe the waste location..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-600"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Pin Location on Map</label>
                            <div id="reportMiniMap"></div>
                            <p class="text-muted small mt-1"><i class="bi bi-hand-index-thumb me-1"></i>Click the map to set exact location, or enter manually below.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Latitude</label>
                            <input type="text" name="latitude" id="modalLat" class="form-control" placeholder="e.g. 28.2096">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-600">Longitude</label>
                            <input type="text" name="longitude" id="modalLng" class="form-control" placeholder="e.g. 83.9856">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom px-4"><i class="bi bi-send me-2"></i>Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== REDEEM CONFIRM MODAL ===== -->
<div class="modal fade" id="redeemModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content rounded-4 border-0 text-center p-4">
            <div class="fs-1 mb-2">🎉</div>
            <h5 class="fw-700" id="redeemTitle">Redeem Product</h5>
            <p class="text-muted small" id="redeemMsg">Are you sure you want to redeem this product?</p>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary-custom btn-sm" id="redeemConfirmBtn">Confirm Redeem</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
    if (tabName === 'map') setTimeout(initCitizenMap, 100);
    if (tabName === 'marketplace') setTimeout(() => AOS.refresh(), 100);
}
document.querySelectorAll('.sidebar-nav a[data-tab]').forEach(link => {
    link.addEventListener('click', e => { e.preventDefault(); switchTab(link.dataset.tab); });
});

// ===== LEAFLET MAP (CITIZEN) =====
let citizenMap = null, reportMiniMap = null;
let selectedLat = null, selectedLng = null;
let markerLayer = null;

const POKHARA = [28.2096, 83.9856];
const mapPins = <?php echo $map_pins_json; ?>;

const statusColors = { pending: '#f59e0b', approved: '#0096c7', collected: '#0077b6' };

function initCitizenMap() {
    if (citizenMap) { citizenMap.invalidateSize(); return; }
    citizenMap = L.map('citizenMap').setView(POKHARA, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors', maxZoom: 19
    }).addTo(citizenMap);

    // Show existing report pins
    mapPins.forEach(pin => {
        const color = statusColors[pin.status] || '#9b1c1c';
        const icon = L.divIcon({
            className: '',
            html: `<div style="width:14px;height:14px;background:${color};border:2px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
            iconSize: [14, 14], iconAnchor: [7, 7]
        });
        const marker = L.marker([pin.lat, pin.lng], { icon }).addTo(citizenMap);
        let popupHtml = `<div style="min-width:160px;font-family:Poppins,sans-serif;">
            <strong>${pin.type}</strong><br>
            <span class="text-muted" style="font-size:0.8rem;">${pin.date}</span><br>
            <span style="font-size:0.8rem;color:${color};font-weight:600;">${pin.status.toUpperCase()}</span>`;
        if (pin.image) popupHtml += `<br><img src="${pin.image}" style="width:100%;margin-top:6px;border-radius:6px;max-height:80px;object-fit:cover;">`;
        popupHtml += `</div>`;
        marker.bindPopup(popupHtml);
    });

    // Click to drop new pin
    let newMarker = null;
    citizenMap.on('click', e => {
        selectedLat = e.latlng.lat.toFixed(6);
        selectedLng = e.latlng.lng.toFixed(6);
        document.getElementById('displayLat').value = selectedLat;
        document.getElementById('displayLng').value = selectedLng;
        document.getElementById('modalLat').value = selectedLat;
        document.getElementById('modalLng').value = selectedLng;
        if (newMarker) citizenMap.removeLayer(newMarker);
        newMarker = L.marker([selectedLat, selectedLng]).addTo(citizenMap)
            .bindPopup('<strong>New Report Location</strong><br>Click "Report Here" to submit.').openPopup();
    });
}

// ===== REPORT MODAL MINI MAP =====
document.getElementById('reportModal').addEventListener('shown.bs.modal', () => {
    if (!reportMiniMap) {
        reportMiniMap = L.map('reportMiniMap').setView(POKHARA, 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(reportMiniMap);
        let miniMarker = null;
        reportMiniMap.on('click', e => {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            document.getElementById('modalLat').value = lat;
            document.getElementById('modalLng').value = lng;
            document.getElementById('displayLat').value = lat;
            document.getElementById('displayLng').value = lng;
            if (miniMarker) reportMiniMap.removeLayer(miniMarker);
            miniMarker = L.marker([lat, lng]).addTo(reportMiniMap);
        });
    } else {
        reportMiniMap.invalidateSize();
    }
    // Pre-fill if already selected
    if (selectedLat) {
        document.getElementById('modalLat').value = selectedLat;
        document.getElementById('modalLng').value = selectedLng;
    }
});

// ===== MARKETPLACE FILTER =====
let currentCat = 'all';
function setCat(el, cat) {
    currentCat = cat;
    document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    filterMarket();
}
function filterMarket() {
    const q = document.getElementById('marketSearch').value.toLowerCase();
    let visible = 0;
    document.querySelectorAll('.product-item').forEach(item => {
        const matchCat = currentCat === 'all' || item.dataset.cat === currentCat;
        const matchSearch = item.dataset.name.includes(q);
        if (matchCat && matchSearch) { item.style.display = ''; visible++; }
        else item.style.display = 'none';
    });
    document.getElementById('noProducts').classList.toggle('d-none', visible > 0);
}

// ===== REDEEM PRODUCT =====
function redeemProduct(name, pts) {
    document.getElementById('redeemTitle').textContent = 'Redeem: ' + name;
    document.getElementById('redeemMsg').textContent = `This will cost ${pts} points. Confirm?`;
    const modal = new bootstrap.Modal(document.getElementById('redeemModal'));
    modal.show();
    document.getElementById('redeemConfirmBtn').onclick = () => {
        modal.hide();
        // Show success toast (frontend demo — backend API can be added later)
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = 9999;
        toast.innerHTML = `<div class="toast show align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle me-2"></i>Redemption request submitted for <strong>${name}</strong>!</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button></div></div>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    };
}
</script>
</body>
</html>
