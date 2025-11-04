<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\Models\User; // <-- PASTIKAN ANDA IMPORT USER MODEL

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
    protected $redirectTo = '/'; // Sesuai permintaan Anda

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

    // --- (PENAMBAHAN BARU) ---
    // Method ini di-override untuk mengubah total logika otentikasi.
    // Kita tidak akan mengecek password di database.
    // -----------------------------------------------------------------
    protected function attemptLogin(Request $request)
    {
        // 1. Ambil input NPK dan Password dari form
        $npk = $request->input($this->username());
        $password = $request->input('password');

        // 2. Cek apakah password yang diketik adalah "password"
        if ($password !== 'password') {
            // Jika BUKAN "password", langsung gagalkan login.
            return false;
        }

        // 3. Jika password BENAR ("password"), cari user berdasarkan NPK
        //    Pastikan Anda sudah 'use App\Models\User;' di bagian atas file.
        $user = User::where($this->username(), $npk)->first();

        // 4. Cek apakah user dengan NPK tersebut ada
        if ($user) {
            // 5. Jika user ada, login-kan dia secara manual
            //    Kita tidak peduli apa password-nya di database.
            $this->guard()->login($user, $request->filled('remember'));
            
            // 6. Kembalikan 'true' untuk menandakan login berhasil
            return true;
        }

        // 7. Jika user dengan NPK itu tidak ditemukan, gagalkan login
        return false;
    }
    // --- (AKHIR PENAMBAHAN BARU) ---


    // --- (INI SUDAH BENAR DARI ANDA) ---
    // Method ini mengganti validasi default
    // agar tidak lagi mencari 'email'
    // ----------------------------------------------------
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string', // Validasi NPK
            'password' => 'required|string',       // Validasi Password
        ]);
    }

    // --- (INI SUDAH BENAR DARI ANDA) ---
    // Method ini memberi tahu Laravel
    // untuk menggunakan 'npk' sebagai field login, BUKAN 'email'
    // ----------------------------------------------------
    public function username()
    {
        return 'npk'; // Ganti 'email' menjadi 'npk'
    }
}
