<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Autentikasi kustom, diporta verbatim dari aksi POST + halaman GET
 * index.php repo asli: login pakai username atau email, token reset
 * disimpan sebagai hash SHA-256 dengan masa berlaku 30 menit, dan pada
 * mode tugas link reset ditampilkan di halaman (tanpa kirim email).
 */
final class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showForgot(): View
    {
        return view('auth.forgot');
    }

    public function showReset(Request $request): View
    {
        return view('auth.reset', ['token' => (string) $request->query('token')]);
    }

    public function register(Request $request): RedirectResponse
    {
        $username = trim((string) $request->input('username'));
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');

        if (! preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 6
        ) {
            return redirect()
                ->route('register')
                ->with('error', 'Data register tidak valid. Username 3-50 karakter dan password minimal 6 karakter.');
        }

        try {
            User::create([
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($password),
            ]);
        } catch (UniqueConstraintViolationException) {
            return redirect()
                ->route('register')
                ->with('error', 'Username atau email sudah digunakan.');
        }

        return redirect()
            ->route('login')
            ->with('success', 'Registrasi berhasil. Silakan login.');
    }

    public function login(Request $request): RedirectResponse
    {
        $login = trim((string) $request->input('login'));
        $password = (string) $request->input('password');

        $user = User::where('username', $login)
            ->orWhere('email', $login)
            ->first();

        if ($user && Hash::check($password, $user->password_hash)) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()
                ->route('home')
                ->with('success', 'Selamat datang kembali, '.$user->username.'!');
        }

        return redirect()
            ->route('login')
            ->with('error', 'Login gagal. Periksa username/email dan password.');
    }

    public function forgot(Request $request): RedirectResponse
    {
        $email = trim((string) $request->input('email'));
        $user = User::where('email', $email)->first();

        if ($user) {
            $token = bin2hex(random_bytes(24));
            $user->forceFill([
                'reset_token_hash' => hash('sha256', $token),
                'reset_expires_at' => now()->addMinutes(30),
            ])->save();

            // Mode tugas: link reset ditampilkan di halaman, bukan dikirim via email.
            $request->session()->flash('dev_reset_url', route('reset', ['token' => $token]));
        }

        return redirect()
            ->route('forgot')
            ->with('success', 'Jika email terdaftar, token reset sudah dibuat. Pada mode tugas, link reset ditampilkan di halaman.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token');
        $password = (string) $request->input('password');
        $hash = hash('sha256', $token);

        $user = User::where('reset_token_hash', $hash)
            ->where('reset_expires_at', '>', now())
            ->first();

        if (! $user || strlen($password) < 6) {
            return redirect()
                ->route('reset', ['token' => $token])
                ->with('error', 'Token reset tidak valid atau password terlalu pendek.');
        }

        $user->forceFill([
            'password_hash' => Hash::make($password),
            'reset_token_hash' => null,
            'reset_expires_at' => null,
        ])->save();

        $request->session()->forget('dev_reset_url');

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil diubah. Silakan login.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
