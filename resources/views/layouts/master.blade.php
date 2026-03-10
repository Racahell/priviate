<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Creative - Bootstrap 3 Responsive Admin Template">
    <meta name="author" content="GeeksLabs">
    <meta name="keyword" content="Creative, Dashboard, Admin, Template, Theme, Bootstrap, Responsive, Retina, Minimal">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <title>{{ $webName ?? 'PrivTuition' }} - @yield('title', 'Dashboard')</title>

    <link href="{{ asset('style.css') }}" rel="stylesheet" />
  </head>

  @php
      $roleClass = auth()->check() ? 'role-'.str_replace('_', '-', auth()->user()->getRoleNames()->first() ?? 'guest') : 'role-guest';
      $currentRole = auth()->check() ? (auth()->user()->getRoleNames()->first() ?? null) : null;
      $shouldPromptLocation = in_array($currentRole, ['admin', 'superadmin'], true);
      $isGuestLayout = !auth()->check();
      $currentYear = now()->year;
      $footerConfig = is_array($webFooterConfig ?? null) ? $webFooterConfig : [];
      $footerBrand = (string) ($footerConfig['brand'] ?? 'Laravel');
      $footerCopyright = (string) ($footerConfig['copyright_text'] ?? '© 2026 Laravel. All rights reserved.');
      $footerVersion = (string) ($footerConfig['version'] ?? 'Version 2.3.1');
      $footerNavigation = preg_split('/\r\n|\r|\n/', (string) ($footerConfig['navigation'] ?? "Tentang Kami\nKontak\nBlog\nFAQ\nHelp Center"));
      $footerLegal = preg_split('/\r\n|\r|\n/', (string) ($footerConfig['legal'] ?? "Privacy Policy\nTerms of Service\nCookie Policy"));
      $footerSocial = preg_split('/\r\n|\r|\n/', (string) ($footerConfig['social'] ?? "Instagram\nFacebook\nLinkedIn\nTwitter/X"));
      $footerContactEmail = (string) ($footerConfig['contact_email'] ?? 'support@privtuition.app');
      $footerContactPhone = (string) ($footerConfig['contact_phone'] ?? '+62 21 5550 2026');
      $footerContactAddress = (string) ($footerConfig['contact_address'] ?? 'Jakarta, Indonesia');
      $brandLogo = null;
      if (!empty($webLogo)) {
          $brandLogo = $webLogo;
      } elseif (file_exists(public_path('img/priviate-logo.png'))) {
          $brandLogo = 'img/priviate-logo.png';
      } elseif (file_exists(public_path('img/priviate-logo.svg'))) {
          $brandLogo = 'img/priviate-logo.svg';
      }
      $greetingHour = now()->hour;
      if ($greetingHour >= 5 && $greetingHour < 12) {
          $timeGreeting = 'Good Morning';
      } elseif ($greetingHour >= 12 && $greetingHour < 15) {
          $timeGreeting = 'Good Afternoon';
      } elseif ($greetingHour >= 15 && $greetingHour < 18) {
          $timeGreeting = 'Good Evening';
      } else {
          $timeGreeting = 'Good Night';
      }
  @endphp
  <body class="{{ $roleClass }} {{ $isGuestLayout ? 'guest-layout' : 'auth-layout' }}">
  <!-- container section start -->
  <section id="container" class="">
      <!--header start-->
      <header class="header dark-bg">
            @auth
                <div class="auth-header-copy">
                    <h2>{{ $timeGreeting }}, {{ explode(' ', Auth::user()->name)[0] }}</h2>
                    <p>Welcome to your PrivTuition dashboard</p>
                </div>
            @endauth

            <!--logo start-->
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="logo">
                @if(!empty($brandLogo))
                    <img src="{{ asset($brandLogo) }}" alt="PriviAte" class="brand-logo brand-logo-top">
                @else
                    <span>PriviAte</span>
                @endif
            </a>
            <!--logo end-->

            <div class="top-nav notification-row">                
                <!-- user login dropdown start-->
                <ul class="nav pull-right top-menu">
                    <!-- user login dropdown end -->
                    @if (Route::has('login'))
                        @auth
                            <li class="dropdown">
                                <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                    <span class="profile-ava">
                                        @if(!empty(Auth::user()->avatar))
                                            <img alt="" src="{{ asset(Auth::user()->avatar) }}">
                                        @else
                                            <span class="profile-fallback">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                        @endif
                                    </span>
                                    <span class="username">{{ Auth::user()->name }}</span>
                                    <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu extended logout">
                                    <div class="log-arrow-up"></div>
                                    <li class="eborder-top">
                                        <a href="{{ route('profile.edit') }}"><i class="icon_profile"></i> My Profile</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('logout') }}" class="logout-btn"><i class="icon_key_alt"></i> Log Out</a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <a href="{{ route('logout') }}" class="btn btn-default btn-sm header-logout">Log Out</a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('login') }}" class="btn btn-primary btn-sm auth-btn auth-login">Log In</a>
                            </li>
                            @if (Route::has('register'))
                                <li>
                                    <a href="{{ route('register') }}" class="btn btn-info btn-sm auth-btn auth-register">Register</a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
                <!-- user login dropdown end -->
            </div>
      </header>      
      <!--header end-->
      <div class="app-overlay" id="appOverlay"></div>

      <!--sidebar start-->
      @auth
        <aside>
            <div id="sidebar"  class="nav-collapse ">
                <div class="sidebar-brand">
                    @if(!empty($brandLogo))
                        <img src="{{ asset($brandLogo) }}" alt="PriviAte" class="brand-logo brand-logo-sidebar">
                    @else
                        <span class="sidebar-brand-dot">◼</span>
                    @endif
                </div>
                <!-- sidebar menu start-->
                <ul class="sidebar-menu">
                    @php
                      $dynamicMenus = collect();
                      $renderMenus = collect();
                      $categorizedRenderMenus = collect();
                      $hideStudentBookingMenu = false;
                      if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('menu_items') && \Illuminate\Support\Facades\Schema::hasTable('menu_permissions')) {
                          $role = auth()->user()->getRoleNames()->first();
                          if ($role === 'superadmin') {
                              $menuQuery = \App\Models\MenuItem::where('is_active', true);
                          } else {
                              $menuIds = \App\Models\MenuPermission::where('role_name', $role)->where('can_view', true)->pluck('menu_item_id');
                              $menuQuery = \App\Models\MenuItem::whereIn('id', $menuIds)->where('is_active', true);
                          }
                          $dynamicMenus = $menuQuery->orderBy('sort_order')->get();
                          if ($role === 'siswa' && \Illuminate\Support\Facades\Schema::hasTable('invoices')) {
                              $menuByRoute = $dynamicMenus->keyBy('route_name');
                              $dynamicMenus = collect([
                                  (object) ['route_name' => 'dashboard', 'label' => data_get($menuByRoute->get('dashboard'), 'label', 'Dashboard')],
                                  (object) ['route_name' => 'student.packages', 'label' => data_get($menuByRoute->get('student.packages'), 'label', 'Paket')],
                                  (object) ['route_name' => 'student.booking', 'label' => data_get($menuByRoute->get('student.booking'), 'label', 'Booking')],
                                  (object) ['route_name' => 'student.invoices', 'label' => data_get($menuByRoute->get('student.invoices'), 'label', 'Invoices')],
                                  (object) ['route_name' => 'profile.edit', 'label' => data_get($menuByRoute->get('profile.edit'), 'label', 'Profil')],
                              ])->filter(fn ($m) => Route::has((string) $m->route_name))->values();
                              $hideStudentBookingMenu = !\App\Models\Invoice::query()
                                  ->where('user_id', auth()->id())
                                  ->where('status', 'paid')
                                  ->exists();
                          } elseif ($role === 'orang_tua') {
                              $menuByRoute = $dynamicMenus->keyBy('route_name');
                              $dynamicMenus = collect([
                                  (object) ['route_name' => 'parent.dashboard', 'label' => 'Dashboard'],
                                  (object) ['route_name' => 'parent.children', 'label' => data_get($menuByRoute->get('parent.children'), 'label', 'Hubungkan Anak')],
                                  (object) ['route_name' => 'parent.schedule', 'label' => data_get($menuByRoute->get('parent.schedule'), 'label', 'Jadwal Anak')],
                                  (object) ['route_name' => 'parent.reschedule', 'label' => data_get($menuByRoute->get('parent.reschedule'), 'label', 'Reschedule')],
                                  (object) ['route_name' => 'parent.disputes', 'label' => data_get($menuByRoute->get('parent.disputes'), 'label', 'Kritik')],
                                  (object) ['route_name' => 'profile.edit', 'label' => data_get($menuByRoute->get('profile.edit'), 'label', 'Profil')],
                              ])->filter(fn ($m) => Route::has((string) $m->route_name))->values();
                          }
                          if (in_array($role, ['admin', 'superadmin'], true) && Route::has('admin.invoices')) {
                              $hasInvoiceMenu = $dynamicMenus->contains(fn ($m) => (string) ($m->route_name ?? '') === 'admin.invoices');
                              if (!$hasInvoiceMenu) {
                                  $dynamicMenus->push((object) ['route_name' => 'admin.invoices', 'label' => 'Invoices']);
                              }
                          }
                          if (in_array($role, ['admin', 'superadmin'], true) && Route::has('admin.reports')) {
                              $hasReportsMenu = $dynamicMenus->contains(fn ($m) => (string) ($m->route_name ?? '') === 'admin.reports');
                              if (!$hasReportsMenu) {
                                  $dynamicMenus->push((object) ['route_name' => 'admin.reports', 'label' => 'Laporan Keuangan']);
                              }
                          }
                          if (in_array($role, ['admin', 'superadmin'], true) && Route::has('admin.settings')) {
                              $hasSettingsMenu = $dynamicMenus->contains(fn ($m) => (string) ($m->route_name ?? '') === 'admin.settings');
                              if (!$hasSettingsMenu) {
                                  $dynamicMenus->push((object) ['route_name' => 'admin.settings', 'label' => 'Setting Web']);
                              }
                          }
                          if (in_array($role, ['admin', 'superadmin'], true) && Route::has('admin.activity.logs')) {
                              $hasActivityMenu = $dynamicMenus->contains(fn ($m) => (string) ($m->route_name ?? '') === 'admin.activity.logs');
                              if (!$hasActivityMenu) {
                                  $dynamicMenus->push((object) ['route_name' => 'admin.activity.logs', 'label' => 'Activity Log']);
                              }
                          }
                          if ($dynamicMenus->isNotEmpty()) {
                              $menuOrderMap = \App\Models\MenuItem::whereIn('route_name', $dynamicMenus->pluck('route_name')->filter()->all())
                                  ->pluck('sort_order', 'route_name');

                              $dynamicMenus = $dynamicMenus
                                  ->sortBy(function ($menu) use ($menuOrderMap) {
                                      if ($menu->route_name === 'dashboard') {
                                          return 0;
                                      }
                                      if ($menu->route_name === 'profile.edit') {
                                          return 9999;
                                      }

                                      return (int) ($menuOrderMap[$menu->route_name] ?? 5000);
                                  })
                                  ->values();

                              $filteredMenus = $dynamicMenus->filter(function ($menu) use ($hideStudentBookingMenu, $role) {
                                  $routeName = (string) ($menu->route_name ?? '');
                                  if ($hideStudentBookingMenu && $routeName === 'student.booking') {
                                      return false;
                                  }
                                  if ($routeName === 'admin.import.center') {
                                      return false;
                                  }
                                  if ($routeName === 'owner.financials') {
                                      return false;
                                  }
                                  if ($role === 'superadmin' && in_array($routeName, ['parent.children', 'parent.reschedule', 'student.booking', 'tutor.wallet'], true)) {
                                      return false;
                                  }
                                  return true;
                              });

                              $filteredMenus = $filteredMenus->map(function ($menu) {
                                  if ((string) ($menu->route_name ?? '') === 'owner.reports') {
                                      $menu->label = 'Laporan Keuangan';
                                  }
                                  return $menu;
                              });

                              $preferredRouteByLabel = [
                                  'kritik' => ['admin.disputes', 'parent.disputes'],
                                  'monitor' => ['admin.monitor'],
                                  'paket' => ['admin.modules.packages', 'student.packages', 'superadmin.modules.packages'],
                                  'sesi' => ['admin.sessions', 'admin.modules.sessions', 'superadmin.modules.sessions'],
                                  'mapel' => ['admin.modules.subjects', 'superadmin.modules.subjects'],
                                  'user' => ['admin.modules.users', 'superadmin.modules.users'],
                                  'invoices' => ['admin.invoices', 'student.invoices'],
                              ];

                              $renderMenus = $filteredMenus
                                  ->groupBy(fn ($menu) => mb_strtolower(trim((string) ($menu->label ?? ''))))
                                  ->map(function ($group, $labelKey) use ($preferredRouteByLabel) {
                                      if (isset($preferredRouteByLabel[$labelKey])) {
                                          $preferred = $preferredRouteByLabel[$labelKey];
                                          foreach ($preferred as $routeName) {
                                              $picked = $group->first(fn ($menu) => (string) $menu->route_name === $routeName);
                                              if ($picked) {
                                                  return $picked;
                                              }
                                          }
                                      }

                                      return $group->first();
                                  })
                                  ->values()
                                  ->sortBy(function ($menu) use ($menuOrderMap) {
                                      if ($menu->route_name === 'dashboard' || $menu->route_name === 'parent.dashboard') {
                                          return 0;
                                      }
                                      if ($menu->route_name === 'profile.edit') {
                                          return 9999;
                                      }

                                      return (int) ($menuOrderMap[$menu->route_name] ?? 5000);
                                  })
                                  ->values();

                              $categoryOrder = ['Utama' => 1, 'Manajemen' => 2, 'Keuangan' => 3, 'Sistem' => 4, 'Akun' => 99];
                              $categorizedRenderMenus = $renderMenus
                                  ->groupBy(function ($menu) {
                                      $route = (string) ($menu->route_name ?? '');
                                      if (in_array($route, ['dashboard', 'parent.dashboard'], true)) return 'Utama';
                                      if ($route === 'profile.edit') return 'Akun';
                                      if (str_contains($route, 'reports') || str_contains($route, 'financials') || str_contains($route, 'wallet') || str_contains($route, 'invoices')) return 'Keuangan';
                                      if (str_contains($route, 'settings') || str_contains($route, 'menu.access') || str_contains($route, 'backup') || str_contains($route, 'import')) return 'Sistem';
                                      return 'Manajemen';
                                  })
                                  ->sortBy(fn ($items, $category) => $categoryOrder[$category] ?? 50);
                          }
                      }
                    @endphp

                    @if($dynamicMenus->isNotEmpty())
                      @foreach($categorizedRenderMenus as $category => $menus)
                          <li class="sidebar-category">{{ $category }}</li>
                          @foreach($menus as $menu)
                              <li class="{{ request()->routeIs($menu->route_name) ? 'active' : '' }}">
                                  <a href="{{ $menu->route_name && Route::has($menu->route_name) ? route($menu->route_name) : '#' }}">
                                      <i class="icon_document_alt"></i>
                                      <span>{{ $menu->label }}</span>
                                  </a>
                              </li>
                          @endforeach
                      @endforeach
                    @else
                      <li class="{{ Request::is('/') ? 'active' : '' }}">
                          <a class="" href="{{ auth()->check() ? route('dashboard') : route('home') }}">
                              <i class="icon_house_alt"></i>
                              <span>Dashboard</span>
                          </a>
                      </li>
                    @endif
                </ul>
                <!-- sidebar menu end-->
            </div>
        </aside>
      @endauth
      <!--sidebar end-->

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
              <!--overview start-->
            @auth
                <div class="row content-heading">
                    <div class="col-lg-12">
                        <h3 class="page-header">@yield('title', 'Dashboard')</h3>
                    </div>
                </div>
            @endauth
              
            @yield('content')
            @if(session('status'))
                <div class="alert alert-success" style="margin-top: 15px;">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger" style="margin-top: 15px;">
                    <ul style="margin:0; padding-left: 18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

          </section>
          <footer class="app-footer">
                  <div class="app-footer-grid">
                      <div>
                          <p class="app-footer-title">{{ $footerBrand }}</p>
                          <p class="app-footer-text">{{ $footerCopyright }}</p>
                          <p class="app-footer-text">{{ $footerVersion }}</p>
                      </div>
                      <div>
                          <p class="app-footer-title">Navigasi</p>
                          <div class="app-footer-links">
                              @foreach($footerNavigation as $line)
                                  @if(trim((string) $line) !== '')
                                      <span>{{ trim((string) $line) }}</span>
                                  @endif
                              @endforeach
                          </div>
                      </div>
                      <div>
                          <p class="app-footer-title">Legal</p>
                          <div class="app-footer-links">
                              @foreach($footerLegal as $line)
                                  @if(trim((string) $line) !== '')
                                      <span>{{ trim((string) $line) }}</span>
                                  @endif
                              @endforeach
                          </div>
                          <p class="app-footer-title">Kontak</p>
                          <p class="app-footer-text">{{ $footerContactEmail }}</p>
                          <p class="app-footer-text">{{ $footerContactPhone }}</p>
                          <p class="app-footer-text">{{ $footerContactAddress }}</p>
                      </div>
                      <div>
                          <p class="app-footer-title">Follow Us</p>
                          <div class="app-footer-links">
                              @foreach($footerSocial as $line)
                                  @if(trim((string) $line) !== '')
                                      <span>{{ trim((string) $line) }}</span>
                                  @endif
                              @endforeach
                          </div>
                      </div>
                  </div>
          </footer>
      </section>
      <!--main content end-->
  </section>
  <!-- container section end -->
    <script>
      (function () {
        var toggle = document.querySelector('.toggle-nav');
        var overlay = document.getElementById('appOverlay');
        if (!toggle || !overlay) return;

        function openSidebar() {
          document.body.classList.add('sidebar-open');
        }

        function closeSidebar() {
          document.body.classList.remove('sidebar-open');
        }

        toggle.addEventListener('click', function (event) {
          if (window.innerWidth > 768) return;
          event.preventDefault();
          event.stopPropagation();
          if (document.body.classList.contains('sidebar-open')) {
            closeSidebar();
          } else {
            openSidebar();
          }
        });

        overlay.addEventListener('click', closeSidebar);
        window.addEventListener('resize', function () {
          if (window.innerWidth > 768) {
            closeSidebar();
          }
        });

        document.querySelectorAll('.dropdown-toggle').forEach(function (trigger) {
          trigger.addEventListener('click', function (event) {
            event.preventDefault();
            var menu = trigger.parentElement.querySelector('.dropdown-menu');
            if (!menu) return;
            document.querySelectorAll('.dropdown-menu.show').forEach(function (openMenu) {
              if (openMenu !== menu) openMenu.classList.remove('show');
            });
            menu.classList.toggle('show');
          });
        });

        document.addEventListener('click', function (event) {
          if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function (openMenu) {
              openMenu.classList.remove('show');
            });
          }
        });
      })();
    </script>
    @auth
    @if($shouldPromptLocation)
    <script>
      (function () {
        if (!navigator.geolocation || sessionStorage.getItem('location_prompted')) return;
        sessionStorage.setItem('location_prompted', '1');
        try {
          alert('Untuk keamanan, Admin/Superadmin wajib mengizinkan akses lokasi perangkat.');
        } catch (e) {}

        navigator.geolocation.getCurrentPosition(
          function (pos) {
            fetch("{{ route('location.consent') }}", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json"
              },
              body: JSON.stringify({
                location_status: "ALLOW",
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude
              })
            });
          },
          function () {
            fetch("{{ route('location.consent') }}", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                "Accept": "application/json"
              },
              body: JSON.stringify({ location_status: "DENIED" })
            });
          },
          {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
          }
        );
      })();
    </script>
    @endif
    @endauth
    @stack('scripts')
  </body>
</html>
