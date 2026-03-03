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

    <title>PrivTuition - @yield('title', 'Dashboard')</title>

    <!-- Bootstrap CSS -->    
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="{{ asset('css/bootstrap-theme.css') }}" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="{{ asset('css/elegant-icons-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/font-awesome.min.css') }}" rel="stylesheet" />    
    <!-- Custom styles -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style-responsive.css') }}" rel="stylesheet" />

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 -->
    <!--[if lt IE 9]>
      <script src="{{ asset('js/html5shiv.js') }}"></script>
      <script src="{{ asset('js/respond.min.js') }}"></script>
      <script src="{{ asset('js/lte-ie7.js') }}"></script>
    <![endif]-->
  </head>

  <body>
  <!-- container section start -->
  <section id="container" class="">
      <!--header start-->
      <header class="header dark-bg">
            <div class="toggle-nav">
                <div class="icon-reorder tooltips" data-original-title="Toggle Navigation" data-placement="bottom"><i class="icon_menu"></i></div>
            </div>

            <!--logo start-->
            <a href="/" class="logo">Priv<span class="lite">Tuition</span></a>
            <!--logo end-->

            <div class="nav search-row" id="top_menu">
                <!--  search form start -->
                <ul class="nav top-menu">                    
                    <li>
                        <form class="navbar-form">
                            <input class="form-control" placeholder="Search" type="text">
                        </form>
                    </li>                    
                </ul>
                <!--  search form end -->                
            </div>

            <div class="top-nav notification-row">                
                <!-- user login dropdown start-->
                <ul class="nav pull-right top-menu">
                    <!-- user login dropdown end -->
                    @if (Route::has('login'))
                        @auth
                            <li class="dropdown">
                                <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                                    <span class="profile-ava">
                                        <img alt="" src="{{ asset('img/avatar1_small.jpg') }}">
                                    </span>
                                    <span class="username">{{ Auth::user()->name }}</span>
                                    <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu extended logout">
                                    <div class="log-arrow-up"></div>
                                    <li class="eborder-top">
                                        <a href="#"><i class="icon_profile"></i> My Profile</a>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" style="border:none; background:none; width:100%; text-align:left; padding: 10px 15px;"><i class="icon_key_alt"></i> Log Out</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('login') }}" class="btn btn-primary btn-sm" style="margin-top: 8px;">Log In</a>
                            </li>
                            @if (Route::has('register'))
                                <li>
                                    <a href="{{ route('register.preverify') }}" class="btn btn-info btn-sm" style="margin-top: 8px; margin-left: 5px;">Register</a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
                <!-- user login dropdown end -->
            </div>
      </header>      
      <!--header end-->

      <!--sidebar start-->
      <aside>
          <div id="sidebar"  class="nav-collapse ">
              <!-- sidebar menu start-->
              <ul class="sidebar-menu">                
                  @php
                    $dynamicMenus = collect();
                    if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('menu_items') && \Illuminate\Support\Facades\Schema::hasTable('menu_permissions')) {
                        $role = auth()->user()->getRoleNames()->first();
                        $menuIds = \App\Models\MenuPermission::where('role_name', $role)->where('can_view', true)->pluck('menu_item_id');
                        $dynamicMenus = \App\Models\MenuItem::whereIn('id', $menuIds)->where('is_active', true)->orderBy('sort_order')->get();
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
                  @else
                    <li class="{{ Request::is('/') ? 'active' : '' }}">
                        <a class="" href="/">
                            <i class="icon_house_alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                  @endif
              </ul>
              <!-- sidebar menu end-->
          </div>
      </aside>
      <!--sidebar end-->

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
              <!--overview start-->
              <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header"><i class="fa fa-laptop"></i> @yield('title', 'Dashboard')</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="/">Home</a></li>
                        <li><i class="fa fa-laptop"></i>@yield('title', 'Dashboard')</li>
                    </ol>
                </div>
            </div>
              
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
      </section>
      <!--main content end-->
  </section>
  <!-- container section end -->
    <!-- javascripts -->
    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <!-- nice scroll -->
    <script src="{{ asset('js/jquery.scrollTo.min.js') }}"></script>
    <script src="{{ asset('js/jquery.nicescroll.js') }}" type="text/javascript"></script>

    <!--custome script for all page-->
    <script src="{{ asset('js/scripts.js') }}"></script>
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
