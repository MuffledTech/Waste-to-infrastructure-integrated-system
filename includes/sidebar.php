<?php
// Determine active page for highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="../index.php" class="sidebar-brand">
            <i class="bi bi-recycle"></i> WIMS Portal
        </a>
        <!-- Close button for mobile can be added here -->
    </div>
    <ul class="sidebar-nav">
        <!-- Common Links -->
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php' || $current_page == 'admin.php' || $current_page == 'citizen.php' || $current_page == 'collector.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 nav-icon"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>
        
        <?php if ($_SESSION['role'] == 'citizen'): ?>
        <li class="nav-item">
            <a href="report_waste.php" class="nav-link <?php echo ($current_page == 'report_waste.php') ? 'active' : ''; ?>">
                <i class="bi bi-camera nav-icon"></i>
                <span class="nav-text">Report Waste</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="my_rewards.php" class="nav-link <?php echo ($current_page == 'my_rewards.php') ? 'active' : ''; ?>">
                <i class="bi bi-trophy nav-icon"></i>
                <span class="nav-text">My Rewards</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'collector'): ?>
        <li class="nav-item">
            <a href="collections.php" class="nav-link <?php echo ($current_page == 'collections.php') ? 'active' : ''; ?>">
                <i class="bi bi-truck nav-icon"></i>
                <span class="nav-text">Collections</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'admin'): ?>
        <li class="nav-item">
            <a href="manage_users.php" class="nav-link <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>">
                <i class="bi bi-people nav-icon"></i>
                <span class="nav-text">Manage Users</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="infrastructure.php" class="nav-link <?php echo ($current_page == 'infrastructure.php') ? 'active' : ''; ?>">
                <i class="bi bi-building nav-icon"></i>
                <span class="nav-text">Infrastructure</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="nav-item">
            <a href="../logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right nav-icon"></i>
                <span class="nav-text">Logout</span>
            </a>
        </li>
    </ul>
</div>
