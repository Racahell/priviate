<ul class="sidebar-menu">                
    <!-- GUEST / PUBLIC -->
    @guest
    <li class="{{ Request::is('/') ? 'active' : '' }}">
        <a class="" href="/">
            <i class="icon_house_alt"></i>
            <span>Home</span>
        </a>
    </li>
    <li>
        <a class="" href="/search-tentor">
            <i class="icon_search"></i>
            <span>Cari Tentor</span>
        </a>
    </li>
    @endguest

    <!-- STUDENT (SISWA) -->
    @can('book_tentor')
    <li class="{{ Request::is('student/dashboard') ? 'active' : '' }}">
        <a class="" href="{{ route('student.dashboard') }}">
            <i class="icon_desktop"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <li>
        <a class="" href="{{ route('student.booking') }}">
            <i class="icon_calendar"></i>
            <span>Booking Les</span>
        </a>
    </li>
    <li>
        <a class="" href="{{ route('student.invoices') }}">
            <i class="icon_document_alt"></i>
            <span>Tagihan</span>
        </a>
    </li>
    @endcan

    <!-- TENTOR -->
    @can('start_session')
    <li class="{{ Request::is('tutor/dashboard') ? 'active' : '' }}">
        <a class="" href="{{ route('tutor.dashboard') }}">
            <i class="icon_desktop"></i>
            <span>Dashboard</span>
        </a>
    </li>
    <li>
        <a class="" href="{{ route('tutor.schedule') }}">
            <i class="icon_calendar"></i>
            <span>Jadwal Mengajar</span>
        </a>
    </li>
    <li>
        <a class="" href="{{ route('tutor.wallet') }}">
            <i class="icon_wallet"></i>
            <span>Dompet & Honor</span>
        </a>
    </li>
    @endcan

    <!-- ADMIN -->
    @can('resolve_dispute')
    <li class="sub-menu">
        <a href="javascript:;" class="">
            <i class="icon_tools"></i>
            <span>Operasional</span>
            <span class="menu-arrow arrow_carrot-right"></span>
        </a>
        <ul class="sub">
            <li><a class="" href="{{ route('admin.kyc') }}">Verifikasi Tentor</a></li>
            <li><a class="" href="{{ route('admin.disputes') }}">Pusat Kritik</a></li>
            <li><a class="" href="{{ route('admin.monitor') }}">Live Monitor</a></li>
        </ul>
    </li>
    @endcan

    <!-- OWNER -->
    @can('view_financial_reports')
    <li class="{{ Request::is('owner/dashboard') ? 'active' : '' }}">
        <a class="" href="{{ route('owner.dashboard') }}">
            <i class="icon_piechart"></i>
            <span>Executive Summary</span>
        </a>
    </li>
    <li>
        <a class="" href="{{ route('owner.financials') }}">
            <i class="icon_currency"></i>
            <span>Laporan Keuangan</span>
        </a>
    </li>
    @endcan

    <!-- SUPERADMIN -->
    @can('configure_whitelabel')
    <li class="sub-menu">
        <a href="javascript:;" class="">
            <i class="icon_cogs"></i>
            <span>System Config</span>
            <span class="menu-arrow arrow_carrot-right"></span>
        </a>
        <ul class="sub">
            <li><a class="" href="{{ route('superadmin.whitelabel') }}">Whitelabeling</a></li>
            <li><a class="" href="{{ route('superadmin.rbac') }}">RBAC Manager</a></li>
            <li><a class="" href="{{ route('superadmin.audit') }}">Audit Logs</a></li>
        </ul>
    </li>
    @endcan
</ul>
