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
      $isGuestLayout = !auth()->check();
      $brandLogo = null;
      if (!empty($webLogo)) {
          $brandLogo = $webLogo;
      } elseif (file_exists(public_path('img/priviate-logo.png'))) {
          $brandLogo = 'img/priviate-logo.png';
      } elseif (file_exists(public_path('img/priviate-logo.svg'))) {
          $brandLogo = 'img/priviate-logo.svg';
      }
  @endphp
  <body class="{{ $roleClass }} {{ $isGuestLayout ? 'guest-layout' : 'auth-layout' }}">
  <!-- container section start -->
  <section id="container" class="">
      <!--header start-->
      <header class="header dark-bg">
            @auth
                <div class="auth-header-copy">
                    <h2>Good Morning, {{ explode(' ', Auth::user()->name)[0] }}</h2>
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
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="logout-btn"><i class="icon_key_alt"></i> Log Out</button>
                                        </form>
                                    </li>
                                </ul>
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
                      if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('menu_items') && \Illuminate\Support\Facades\Schema::hasTable('menu_permissions')) {
                          $role = auth()->user()->getRoleNames()->first();
                          if ($role === 'superadmin') {
                              $menuQuery = \App\Models\MenuItem::where('is_active', true);
                              $menuQuery->where(function ($q) {
                                  $q->whereIn('route_name', ['dashboard', 'profile.edit'])
                                      ->orWhere('route_name', 'like', 'superadmin.%');
                              });
                          } else {
                              $menuIds = \App\Models\MenuPermission::where('role_name', $role)->where('can_view', true)->pluck('menu_item_id');
                              $menuQuery = \App\Models\MenuItem::whereIn('id', $menuIds)->where('is_active', true);
                          }
                          $dynamicMenus = $menuQuery->orderBy('sort_order')->get();
                      }
                    @endphp

                    @if($dynamicMenus->isNotEmpty())
                      @foreach($dynamicMenus as $menu)
                          <li class="{{ request()->routeIs($menu->route_name) ? 'active' : '' }}">
                              <a href="{{ $menu->route_name && Route::has($menu->route_name) ? route($menu->route_name) : '#' }}">
                                  <i class="icon_document_alt"></i>
                                  <span>{{ $menu->label }}</span>
                              </a>
                          </li>
                      @endforeach
                      @if(auth()->user()->hasRole('orang_tua') && Route::has('parent.children') && !$dynamicMenus->contains(fn($m) => $m->route_name === 'parent.children'))
                          <li class="{{ request()->routeIs('parent.children') ? 'active' : '' }}">
                              <a href="{{ route('parent.children') }}">
                                  <i class="icon_key"></i>
                                  <span>Hubungkan Anak</span>
                              </a>
                          </li>
                      @endif
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
              <div class="text-center">
                  {{ $webFooter ?: 'PrivTuition' }}
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
    <script>
      (function () {
        if (!navigator.geolocation || sessionStorage.getItem('location_prompted')) return;
        sessionStorage.setItem('location_prompted', '1');

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
          }
        );
      })();
    </script>
    @endauth
    @stack('scripts')
  </body>
</html>
