{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
    <div class="w-full max-w-md mx-auto bg-white rounded-xl shadow-lg p-6 space-y-6">
        
        {{-- Logo --}}
        <div class="flex justify-center">
            <img src="{{ asset('assets/images/AVI.png') }}" 
                 alt="Astra Visteon Indonesia" 
                 class="h-20 w-auto">
        </div>

        {{-- Error Message --}}
        @if ($errors->has('login'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $errors->first('login') }}</span>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            {{-- NPK --}}
            <div>
                <label for="npk" class="block text-sm font-medium text-gray-700">NPK</label>
                <input id="npk" type="text" name="npk" required 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input id="password" type="password" name="password" required 
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            {{-- Button --}}
            <div>
                <button type="submit" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Login
                </button>
            </div>
        </form>
    </div>
</x-guest-layout>
