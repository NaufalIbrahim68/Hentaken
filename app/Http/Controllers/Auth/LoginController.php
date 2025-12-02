<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;  


class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Method ini memberi tahu Laravel untuk menggunakan 'npk' sebagai field login
     */
    public function username()
    {
        return 'npk';
    }

    /**
     * Override credentials untuk memastikan menggunakan npk
     */
    protected function credentials(Request $request)
    {
        return [
            'npk' => $request->npk,
            'password' => $request->password,
        ];
    }

    /**
     * Validasi input login
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'npk' => 'required|string',
            'password' => 'required|string',
        ]);
    }
//     protected function attemptLogin(Request $request)
// {
//     // Debug log
//     Log::info('Attempting login', [
//         'npk' => $request->npk,
//         'credentials' => $this->credentials($request),
//     ]);

//     $result = $this->guard()->attempt(
//         $this->credentials($request),
//         $request->filled('remember')
//     );

//     Log::info('Login result', [
//         'success' => $result,
//     ]);

//     return $result;
// }
}