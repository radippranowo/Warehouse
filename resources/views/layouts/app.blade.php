<!doctype html>
<html lang="en">

<head>
    <base href="{{ url('/') }}/">
    <meta charset="utf-8" />
    <title>Logistik</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Logistik Admin" name="description" />
    <meta content="Apex-inspired" name="author" />

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Public Sans (Apex/Vuexy signature font) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Existing libs (kept for page-level compatibility) -->
    <link href="{{ asset('assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        #side-menu .sub-menu { display: none; }
        #side-menu li.mm-active > .sub-menu { display: block; }
    </style>
</head>

<body data-sidebar="dark" data-layout-mode="light">
    <div id="layout-wrapper">

        @persist('topbar')
        <!-- ============== TOPBAR ============== -->
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box">
                        <a href="{{ route('dashboard.index') }}" class="logo logo-dark">
                            <span class="logo-sm">
                                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="20">
                            </span>
                        </a>
                        <a href="{{ route('dashboard.index') }}" class="logo logo-light">
                            <span class="logo-sm">
                                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ asset('assets/images/logo-light.png') }}" alt="" height="20">
                            </span>
                        </a>
                    </div>

                    <button type="button" class="btn btn-sm px-3 font-size-16 header-item waves-effect" id="vertical-menu-btn">
                        <i class="fa fa-fw fa-bars"></i>
                    </button>
                </div>

                <div class="d-flex">
                    <div class="dropdown d-none d-lg-inline-block ms-1" wire:ignore>
                        <button type="button" class="btn header-item noti-icon waves-effect" data-bs-toggle="fullscreen">
                            <i class="bx bx-fullscreen"></i>
                        </button>
                    </div>

                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item noti-icon waves-effect position-relative"
                            id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-bell bx-tada"></i>
                            <span class="badge bg-danger rounded-pill">3</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
                            <div class="p-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="m-0"> Notifications </h6>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#!" class="small"> View All</a>
                                    </div>
                                </div>
                            </div>
                            <div data-simplebar style="max-height: 230px;">
                                <a href="javascript: void(0);" class="text-reset notification-item">
                                    <div class="d-flex">
                                        <div class="avatar-xs me-3">
                                            <span class="avatar-title bg-primary rounded-circle font-size-16">
                                                <i class="bx bx-cart"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Your order is placed</h6>
                                            <div class="font-size-12 text-muted">
                                                <p class="mb-1">If several languages coalesce the grammar</p>
                                                <p class="mb-0"><i class="mdi mdi-clock-outline"></i> <span>3 min ago</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <a href="javascript: void(0);" class="text-reset notification-item">
                                    <div class="d-flex">
                                        <div class="avatar-xs me-3">
                                            <span class="avatar-title bg-success rounded-circle font-size-16">
                                                <i class="bx bx-badge-check"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">Your item is shipped</h6>
                                            <div class="font-size-12 text-muted">
                                                <p class="mb-1">If several languages coalesce the grammar</p>
                                                <p class="mb-0"><i class="mdi mdi-clock-outline"></i> <span>1 hours ago</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="p-2 border-top d-grid">
                                <a class="btn btn-sm btn-link font-size-14 text-center" href="javascript:void(0)">
                                    <i class="mdi mdi-arrow-right-circle me-1"></i> <span>View More..</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item waves-effect" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img class="rounded-circle header-profile-user" src="{{ asset('assets/images/users/avatar-1.jpg') }}" alt="Header Avatar">
                            <span class="d-none d-xl-inline-block ms-1">Henry</span>
                            <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="#"><i class="bx bx-user font-size-16 align-middle me-1"></i> Profile</a>
                            <a class="dropdown-item" href="#"><i class="bx bx-wallet font-size-16 align-middle me-1"></i> My Wallet</a>
                            <a class="dropdown-item d-block" href="#"><span class="badge bg-success float-end">11</span><i class="bx bx-wrench font-size-16 align-middle me-1"></i> Settings</a>
                            <a class="dropdown-item" href="#"><i class="bx bx-lock-open font-size-16 align-middle me-1"></i> Lock screen</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#"><i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        @endpersist

        @persist('sidebar')
        <!-- ============== SIDEBAR ============== -->
        <div class="vertical-menu">
            <div data-simplebar class="h-100">
                <div id="sidebar-menu">
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li>
                            <a href="{{ route('dashboard.index') }}" wire:navigate.hover class="waves-effect">
                                <i class="bx bx-home-circle"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="menu-title" key="t-menu">Apps &amp; Pages</li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-package"></i>
                                <span>Data Master</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ route('barang.index') }}" wire:navigate.hover>Barang</a></li>
                                <li><a href="{{ route('category.index') }}" wire:navigate.hover>Kategori</a></li>
                                <li><a href="{{ route('merk.index') }}" wire:navigate.hover>Merk</a></li>
                                <li><a href="{{ route('group.index') }}" wire:navigate.hover>Group</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="bx bx-transfer"></i>
                                <span>Transaksi</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ route('barangmasuk.index') }}" wire:navigate.hover>Barang Masuk</a></li>
                                <li><a href="{{ route('barangkeluar.index') }}" wire:navigate.hover>Barang Keluar</a></li>
                            </ul>
                        </li>

                        <li>
                            <a href="{{ route('pr.index') }}" wire:navigate.hover class="waves-effect">
                                <i class="bx bx-receipt"></i>
                                <span>Pre-Order</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        @endpersist

        <!-- ============== MAIN ============== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    @livewireScripts

    <script>
        if (window.location.hostname !== 'localhost') {
            const originalLog = console.log;
            console.log = function() {
                if (arguments[0] === 'pressed') return;
                originalLog.apply(console, arguments);
            };
        }
    </script>

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/libs/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jquery.repeater/jquery.repeater.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-repeater.int.js') }}"></script>

    <script data-navigate-once>
        // Sidebar toggle (works through wire:navigate)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('#vertical-menu-btn');
            if (!btn) return;
            e.preventDefault();

            var body = document.body;
            if (window.innerWidth >= 992) {
                var size = body.getAttribute('data-sidebar-size');
                body.setAttribute('data-sidebar-size', (!size || size === 'lg') ? 'sm' : 'lg');
                body.classList.remove('sidebar-enable');
            } else {
                body.classList.toggle('sidebar-enable');
                body.removeAttribute('data-sidebar-size');
            }
        });

        function syncActiveMenu() {
            if (!window.jQuery) return;
            var $menu = jQuery('#side-menu');
            if (!$menu.length) return;

            var path = window.location.pathname.replace(/\/+$/, '') || '/';

            $menu.find('a').removeClass('active mm-active');
            $menu.find('li').removeClass('mm-active');
            $menu.find('ul.sub-menu').removeClass('mm-show').attr('aria-expanded', 'false');
            $menu.find('a.has-arrow').attr('aria-expanded', 'false');

            $menu.find('a').each(function () {
                var href = this.getAttribute('href') || '';
                if (!href || href.indexOf('javascript') === 0) return;
                try { href = new URL(this.href).pathname.replace(/\/+$/, '') || '/'; } catch (e) { return; }
                if (href !== path) return;

                var $a = jQuery(this);
                $a.addClass('active');
                $a.parents('li').addClass('mm-active');
                $a.parents('ul.sub-menu').addClass('mm-show').attr('aria-expanded', 'true');
                $a.parents('ul.sub-menu').siblings('a.has-arrow').addClass('mm-active').attr('aria-expanded', 'true');
            });
        }

        function runSync() {
            syncActiveMenu();
            requestAnimationFrame(syncActiveMenu);
        }

        if (window.jQuery) {
            jQuery(runSync);
        } else if (document.readyState !== 'loading') {
            runSync();
        } else {
            document.addEventListener('DOMContentLoaded', runSync);
        }

        document.addEventListener('livewire:navigated', runSync);
    </script>

</body>
</html>
