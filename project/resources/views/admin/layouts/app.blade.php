<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('/assets/app.css') }}">

</head>

<body>
    <nav class="navbar navbar-dark navbar-theme-primary px-4 col-12 d-md-none">
        <a class="navbar-brand me-lg-5" href="{{ route('home') }}">
            <img class="navbar-brand-dark" src="{{ asset('assets/images/brand/light.svg') }}" alt="Volt logo" />
            <img class="navbar-brand-light" src="{{ asset('assets/images/brand/dark.svg') }}" alt="Volt logo" />
        </a>
        <div class="d-flex align-items-center">
            <button class="navbar-toggler d-md-none collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    <nav id="sidebarMenu" class="sidebar d-lg-block bg-gray-800 text-white collapse" data-simplebar>
        <div class="sidebar-inner px-2 pt-3">
            @include('admin.layouts.responsive-topbar')
            @include('admin.layouts.navigation')
        </div>
    </nav>
    <main class="content">
        {{-- TopBar --}}
        @include('admin.layouts.topbar')
        @yield('content')
    </main>

    <footer class="bg-white rounded shadow p-5 mb-4 mt-4">
        <div class="row">
            <div class="col-12 col-md-4 col-xl-6 mb-4 mb-md-0">
                <p class="mb-0 text-center text-lg-start">© <span class="current-year"></span> 3kary</p>
            </div>
            <div class="col-12 col-md-8 col-xl-6 text-center text-lg-start">

            </div>
        </div>
    </footer>
    @include('flash::message')

    <script src="{{ asset('/assets/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('/assets/app.js') }}"></script>
</body>

</html>
