<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title inertia>Warehouse</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <link href="{{ asset('assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; background-color: transparent; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.05); border-radius: 10px; transition: all 0.3s ease; }
        body:hover::-webkit-scrollbar-thumb,
        .table-responsive:hover::-webkit-scrollbar-thumb { background-color: rgba(0,0,0,0.2); }
        * { scrollbar-width: thin; scrollbar-color: rgba(0,0,0,0.1) transparent; }

        .main-content, .page-content, #layout-wrapper { min-height: auto !important; height: auto !important; }
        body { height: auto !important; min-height: auto !important; }
        .simplebar-track { display: none !important; }
        .container-fluid { max-width: 100% !important; width: 100% !important; padding-bottom: 20px !important; }

        .modal-blur { background-color: rgba(0,0,0,0.18); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(4px); transition: backdrop-filter 0.2s ease; }

        body:fullscreen,
        body:-webkit-full-screen,
        body:-moz-full-screen,
        body:-ms-fullscreen { background-color: #f8f8fb !important; overflow: auto !important; }

        .main-content { transition: margin-left 0.3s ease; }
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0 !important; }
            .vertical-menu { display: none; }
            body.sidebar-enable .vertical-menu { display: block; }
        }

    </style>

    <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @vite(['resources/js/app.js'])
    @inertiaHead
</head>

<body data-sidebar="dark" data-layout-mode="light">
    @inertia
</body>

</html>
