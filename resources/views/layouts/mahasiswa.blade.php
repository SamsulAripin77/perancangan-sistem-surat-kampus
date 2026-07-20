<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        @include('partials.topbar')
        @include('partials.sidebar-mahasiswa')

        <main class="app-main">
            @include('partials.breadcrumb', ['title' => $title ?? '', 'breadcrumbs' => $breadcrumbs ?? []])

            <div class="app-content">
                <div class="container-fluid">
                    @include('partials.flash')
                    @yield('content')
                </div>
            </div>
        </main>

        @include('partials.footer')
    </div>
</body>

</html>
