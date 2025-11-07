<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Henkaten</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- ===== BLOK NOTIFIKASI DITAMBAHKAN DI SINI ===== -->
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-4">
            <!-- Pesan Sukses (Success) -->
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 transform" x-transition:leave-end="opacity-0 transform scale-90"
                    x-init="setTimeout(() => show = false, 5000)"
                    class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-md shadow-md relative"
                    role="alert">
                    <p class="font-bold">Sukses</p>
                    <p>{{ session('success') }}</p>
                    <button @click="show = false"
                        class="absolute top-2 right-2 text-green-700 hover:text-green-900 font-bold">&times;</button>
                </div>
            @endif

            <!-- Pesan Error (Error) -->
            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 transform" x-transition:leave-end="opacity-0 transform scale-90"
                    x-init="setTimeout(() => show = false, 5000)"
                    class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md shadow-md relative"
                    role="alert">
                    <p class="font-bold">Terjadi Kesalahan</p>
                    <p>{{ session('error') }}</p>
                    <button @click="show = false"
                        class="absolute top-2 right-2 text-red-700 hover:text-red-900 font-bold">&times;</button>
                </div>
            @endif

            <!-- Pesan Peringatan (Warning) -->
            @if (session('warning'))
                <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 transform" x-transition:leave-end="opacity-0 transform scale-90"
                    x-init="setTimeout(() => show = false, 5000)"
                    class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-md shadow-md relative"
                    role="alert">
                    <p class="font-bold">Peringatan</p>
                    <p>{{ session('warning') }}</p>
                    <button @click="show = false"
                        class="absolute top-2 right-2 text-yellow-700 hover:text-yellow-900 font-bold">&times;</button>
                </div>
            @endif

            <!-- Error Validasi Form (Validation Errors) -->
            @if ($errors->any())
                <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300"
                    x-transition:leave-start="opacity-100 transform" x-transition:leave-end="opacity-0 transform scale-90"
                    class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-md shadow-md relative"
                    role="alert">
                    <p class="font-bold">Terdapat kesalahan input:</p>
                    <ul class="list-disc list-inside mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button @click="show = false"
                        class="absolute top-2 right-2 text-red-700 hover:text-red-900 font-bold">&times;</button>
                </div>
            @endif
        </div>
        <!-- ===== AKHIR BLOK NOTIFIKASI ===== -->


        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white shadow">
                {{-- PERUBAHAN: Diberi padding top lebih kecil agar notif tidak terlalu jauh --}}
                <div class="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('scripts')
</body>

</html>