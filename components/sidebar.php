<!-- components/sidebar.php -->
<style>
/* ===== Professional Sidebar with Multi-Color Accents ===== */
.sidebar-glass {
    background: #343a40; /* dashboard background */
    color: #fff !important;
    height: 100vh;
    overflow-y: auto;
    position: fixed;
    width: 250px;
    z-index: 1000;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Brand */
.sidebar-glass .brand-link {
    background: #23272b;
    color: #fff !important;
    font-weight: 700;
    font-size: 1.25rem;
    text-align: center;
    padding: 1rem 0;
    border-bottom: 1px solid #3c4043;
    border-radius: 0 0 12px 12px;
}

/* Top buttons */
.sidebar-top-buttons {
    display: flex;
    justify-content: space-around;
    margin: 1rem 0;
}
.sidebar-top-buttons .top-btn {
    background: rgba(0, 123, 255, 0.1); 
    color: #fff !important;
    padding: 0.45rem 0.8rem;
    border-radius: 22px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}
.sidebar-top-buttons .top-btn.active,
.sidebar-top-buttons .top-btn:hover {
    background: rgba(0, 123, 255, 0.25);
}

/* Navigation Links */
.sidebar-glass .nav-sidebar .nav-link {
    color: #fff !important;
    border-radius: 8px;
    padding: 0.55rem 1rem;
    margin: 5px 0;
    display: flex;
    align-items: center;
    transition: all 0.25s ease;
}
.sidebar-glass .nav-sidebar .nav-link.active {
    background: rgba(0, 123, 255, 0.25); 
}
.sidebar-glass .nav-sidebar .nav-link:hover {
    background: rgba(0, 123, 255, 0.15);
    transform: translateX(3px);
}

/* Icons - cycle colors */
.sidebar-glass .nav-item:nth-child(1) .nav-icon { color: #dc3545; } /* red */
.sidebar-glass .nav-item:nth-child(2) .nav-icon { color: #28a745; } /* green */
.sidebar-glass .nav-item:nth-child(3) .nav-icon { color: #ffc107; } /* yellow */
.sidebar-glass .nav-item:nth-child(4) .nav-icon { color: #007bff; } /* blue */
.sidebar-glass .nav-item:nth-child(5) .nav-icon { color: #17a2b8; } /* cyan */
.sidebar-glass .nav-item:nth-child(6) .nav-icon { color: #6f42c1; } /* purple */
.sidebar-glass .nav-item:nth-child(7) .nav-icon { color: #fd7e14; } /* orange */
.sidebar-glass .nav-item:nth-child(8) .nav-icon { color: #20c997; } /* teal */
.sidebar-glass .nav-item:nth-child(9) .nav-icon { color: #e83e8c; } /* pink */
.sidebar-glass .nav-item:nth-child(10) .nav-icon { color: #343a40; } /* dark */

/* Treeview arrows */
.sidebar-glass .right {
    color: #007bff;
    transition: transform 0.3s ease;
}
.sidebar-glass .nav-item.menu-open > .nav-link > .right {
    transform: rotate(90deg);
}

/* Scrollbar */
.sidebar-glass::-webkit-scrollbar {
    width: 8px;
}
.sidebar-glass::-webkit-scrollbar-thumb {
    background: rgba(0, 123, 255, 0.3);
    border-radius: 10px;
}
.sidebar-glass::-webkit-scrollbar-track {
    background: transparent;
}

/* Logout button at bottom */
.sidebar-logout {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
}
.sidebar-logout a {
    display: block;
    text-align: center;
    padding: 10px 0;
    background: #dc3545; 
    color: #fff !important;
    border-radius: 45px;
    font-weight: 600;
    transition: all 0.25s ease;
    text-decoration: none;
}
.sidebar-logout a:hover {
    background: #c82333;
}
</style>

<aside class="main-sidebar sidebar-glass elevation-4">
    <!-- Brand -->
    <a href="/" class="brand-link">
        <span class="brand-text font-weight-bold">WiFiBilling Panel</span>
    </a>

    <div class="sidebar">
        <!-- Top buttons -->
        <div class="sidebar-top-buttons">
            <a href="dashboard" class="top-btn active">Home</a>
            <a href="routers" class="top-btn">Routers</a>
            <a href="users" class="top-btn">Users</a>
        </div>

        <!-- Navigation -->
        <nav class="mt-3">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview">
                <li class="nav-item">
                    <a href="dashboard" class="nav-link active">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="routers" class="nav-link">
                        <i class="nav-icon fas fa-network-wired"></i>
                        <p>View Routers</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="add_router" class="nav-link">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Add Router</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="users" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Manage Users</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-user-plus"></i>
                        <p>Add User</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="billing" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>Billing</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="plans" class="nav-link">
                        <i class="nav-icon fas fa-layer-group"></i>
                        <p>Plans</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="reports" class="nav-link">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Reports</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="logs" class="nav-link">
                        <i class="nav-icon fas fa-file-alt"></i>
                        <p>Activity Logs</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="support" class="nav-link">
                        <i class="nav-icon fas fa-headset"></i>
                        <p>Support</p>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Logout button -->
        <div class="sidebar-logout">
            <a href="logout">Logout</a>
        </div>
    </div>
</aside>
