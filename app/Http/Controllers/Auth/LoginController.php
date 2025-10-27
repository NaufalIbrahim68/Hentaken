<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request; // <-- TAMBAHKAN INI

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/'; // Saya ganti ke '/' sesuai route dashboard Anda

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    // --- (PENAMBAHAN) ---
    // Method ini mengganti validasi default
    // agar tidak lagi mencari 'email'
    // ----------------------------------------------------
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string', // Validasi NPK
            'password' => 'required|string',      // Validasi Password
        ]);
    }

    // --- (PENAMBAHAN) ---
    // Method ini memberi tahu Laravel
    // untuk menggunakan 'npk' sebagai field login, BUKAN 'email'
    // ----------------------------------------------------
    public function username()
    {
        return 'npk'; // Ganti 'email' menjadi 'npk'
    }
}

