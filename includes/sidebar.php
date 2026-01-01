<?php
// Get alert count
$alertResult = $db->query("SELECT COUNT(*) as count FROM alerts WHERE status = 'new'");
$alertCount = $alertResult->fetch_assoc()['count'];
?>
<nav class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-droplet"></i>
            <span>JEMA</span>
        </div>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <?php if (isAdmin()): ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'water-supply.php' ? 'active' : ''; ?>" href="water-supply.php">
                <i class="fas fa-faucet"></i>
                <span>Water Supply</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'usage.php' ? 'active' : ''; ?>" href="usage.php">
                <i class="fas fa-chart-bar"></i>
                <span>Water Usage</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'billing.php' ? 'active' : ''; ?>" href="billing.php">
                <i class="fas fa-receipt"></i>
                <span>Billing</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                <i class="fas fa-credit-card"></i>
                <span>Payments</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                <i class="fas fa-file-pdf"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'alerts.php' ? 'active' : ''; ?>" href="alerts.php">
                <i class="fas fa-bell"></i>
                <span>Alerts</span>
                <?php if ($alertCount > 0): ?>
                    <span class="badge bg-danger"><?php echo $alertCount; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : ''; ?>" href="complaints.php">
                <i class="fas fa-exclamation-circle"></i>
                <span>Complaints</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                <i class="fas fa-user-tie"></i>
                <span>Users</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                <i class="fas fa-user-circle"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</nav>
