<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('admin.dashboard') }}" class="b-brand text-primary">
                <!-- ========   Change your logo from here   ============ -->
                <img src="{{ asset('public/assets/images/tjiniapp-logo-dark.png') }}" class="img-fluid logo-lg" alt="logo">
            </a>
        </div>
        <div class="navbar-content">
            <ul class="pc-navbar">
                <li class="pc-item">
                    <a href="{{ route('admin.dashboard') }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-dashboard"></i></span>
                        <span class="pc-mtext">Dashboard</span>
                    </a>
                </li>

                <li class="pc-item pc-caption">
                    <label>Modules</label>
                    <i class="ti ti-dashboard"></i>
                </li>
                <li class="pc-item {{ request()->is('admin/schools*') ? 'active' : '' }}">
                    <a href="{{ route('admin.schools.index') }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-school"></i></span>
                        <span class="pc-mtext">Schools</span>
                    </a>
                </li>
                <li class="pc-item {{ request()->is('admin/users/parent*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index', 'parent') }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-user"></i></span>
                        <span class="pc-mtext">Parents</span>
                    </a>
                </li>
                <li class="pc-item {{ request()->is('admin/users/staff*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index', 'staff') }}" class="pc-link">
                        <span class="pc-micon"><i class="ti ti-users"></i></span>
                        <span class="pc-mtext">Staff</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<!-- [ Sidebar Menu ] end -->