{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
    {{-- Logo --}}
    <div class="flex justify-center mb-6">
        <img src="{{ asset('assets/images/AVI.png') }}" 
             alt="Astra Visteon Indonesia" 
             class="h-20 w-auto">
    </div>

    

    {{-- Error Message Global --}}
    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        {{-- NPK --}}
        <div>
            <label for="npk" class="block text-sm font-medium text-gray-700">NPK</label>
            <input id="npk" 
                   type="text" 
                   name="npk" 
                   value="{{ old('npk') }}"
                   required 
                   autofocus
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('npk') border-red-500 @enderror">
            @error('npk')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" 
                   type="password" 
                   name="password" 
                   required 
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-red-500 @enderror">
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center">
            <input id="remember" 
                   type="checkbox" 
                   name="remember" 
                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="remember" class="ml-2 block text-sm text-gray-900">
                Ingat Saya
            </label>
        </div>

        {{-- Button --}}
        <div>
            <button type="submit" 
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Login
            </button>
        </div>
    </form>
</x-guest-layout>