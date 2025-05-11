<?php
$role = $_SESSION['role'] ?? 'viewer';
$lang = $_SESSION['lang'] ?? 'en';
$is_viewer = $role === 'viewer';

// Detect current page for active link
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="dashboard.php" class="brand-link">
        <img src="../assets/images/logo.jpg" alt="Werabe Land Lease Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light"><?php echo $lang === 'am' ? 'ወራቤ መሬት ሊዝ' : 'Werabe Land Lease'; ?></span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p><?php echo $lang === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?></p>
                    </a>
                </li>

                <?php if (!$is_viewer): ?>
                    <!-- Manage Owners -->
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php']) && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-users"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ባለቤቶች' : 'Owners'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="owner/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ባለቤት ጨምር' : 'Add Owner'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="owner/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'owner/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ባለቤቶች' : 'List Owners'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Manage Assets -->
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php', 'edit.php', 'delete.php', 'history.php', 'transfer.php']) && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-building"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ንብረቶች' : 'Assets'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="asset/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ንብረት ጨምር' : 'Add Asset'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="asset/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ንብረቶች' : 'List Assets'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="asset/history.php" class="nav-link <?php echo $current_page === 'history.php' && str_contains($_SERVER['REQUEST_URI'], 'asset/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'የሽግግር ታሪክ' : 'Transfer History'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Manage Leases -->
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-contract"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ሊዞች' : 'Leases'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="lease/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ሊዝ ጨምር' : 'Add Lease'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="lease/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'lease/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ሊዞች' : 'List Leases'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (in_array($role, ['admin', 'billing_officer', 'clerk'])): ?>
                    <!-- Manage Bills -->
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-file-invoice"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ቢሎች' : 'Bills'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="bills/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ቢል ጨምር' : 'Add Bill'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="bills/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'bills/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ቢሎች' : 'List Bills'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Manage Payments -->
                    <li class="nav-item <?php echo in_array($current_page, ['add.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-money-check-alt"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ክፍያዎች' : 'Payments'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="payments/add.php" class="nav-link <?php echo $current_page === 'add.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Payment'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="payments/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'payments/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Payments'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Manage Charges -->
                    <li class="nav-item <?php echo in_array($current_page, ['add_charge.php', 'list_charges.php']) && str_contains($_SERVER['REQUEST_URI'], 'charges/') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ክፍያዎች' : 'Charges'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="../charges/add_charge.php" class="nav-link <?php echo $current_page === 'add_charge.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ክፍያ ጨምር' : 'Add Charge'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../charges/list_charge.php" class="nav-link <?php echo $current_page === 'list_charges.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ክፍያዎች' : 'List Charges'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

                <!-- Reports -->
                <li class="nav-item <?php echo in_array($current_page, ['financial_report.php', 'lease_report.php']) ? 'menu-open' : ''; ?>">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            <?php echo $lang === 'am' ? 'ሪፖርቶች' : 'Reports'; ?>
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="reports/financial_report.php" class="nav-link <?php echo $current_page === 'financial_report.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p><?php echo $lang === 'am' ? 'የፋይናንስ ሪፖርት' : 'Financial Report'; ?></p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports/lease_report.php" class="nav-link <?php echo $current_page === 'lease_report.php' ? 'active' : ''; ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p><?php echo $lang === 'am' ? 'የሊዝ ሪፖርት' : 'Lease Report'; ?></p>
                            </a>
                        </li>
                    </ul>
                </li>

                <?php if ($role === 'admin'): ?>
                    <!-- Manage Users -->
                    <li class="nav-item <?php echo in_array($current_page, ['register.php', 'list.php']) && str_contains($_SERVER['REQUEST_URI'], '/user') ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>
                                <?php echo $lang === 'am' ? 'ተጠቃሚዎች' : 'Users'; ?>
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="register.php" class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ተጠቃሚ ጨምር' : 'Add User'; ?></p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="users/list.php" class="nav-link <?php echo $current_page === 'list.php' && str_contains($_SERVER['REQUEST_URI'], 'users/') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p><?php echo $lang === 'am' ? 'ዝርዝር ተጠቃሚዎች' : 'List Users'; ?></p>
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php endif; ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>