
@php
    // Cek apakah Auth::user() ada, jika tidak pakai dummy
    $user = Auth::user() ?? (object)[
        'name' => 'admin',
        'npk' => '00000',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">

            {{-- Navigation --}}
            <nav class="bg-white border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                    
                    {{-- Logo AVI --}}
                    <div class="flex items-center space-x-2">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('assets/images/AVI.png') }}" 
                                 alt="Logo AVI" 
                                 class="h-10 w-auto">
                        </a>
                    </div>

                    {{-- Include Navigation Links --}}
                    <div>
                        @include('layouts.navigation')
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
           <main>
    @yield('content')
</main>
        </div>
    </body>
</html>
