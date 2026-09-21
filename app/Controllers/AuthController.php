<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Services\AuthService;

final class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function showRegister(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }
        Response::view('auth/register', ['title' => 'Create your account']);
    }

    public function register(Request $request): void
    {
        $data = $request->all();
        $validator = new Validator($data);
        $validator->required('name', 'Name')->string('name', 'Name', 2, 120)
            ->required('email', 'Email')->email('email')
            ->required('password', 'Password')->minLength('password', 'Password', 8)
            ->same('password_confirmation', 'password', 'Password confirmation');

        $errors = $validator->errors();
        $users = new User();
        if (!empty($data['email']) && $users->emailExists(mb_strtolower(trim($data['email'])))) {
            $errors['email'][] = 'An account with this email already exists.';
        }

        if (!empty($errors)) {
            Session::set('_old', $data);
            $this->respond($request, false, 'Please correct the errors below.', ['errors' => $errors], '/register');
            return;
        }

        $user = $this->auth->register($data['name'], $data['email'], $data['password']);
        Auth::login($user);
        Response::redirect(url('/dashboard'));
    }

    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }
        Response::view('auth/login', ['title' => 'Log in']);
    }

    public function login(Request $request): void
    {
        $config = config('app');
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        if ($this->auth->isLockedOut($email, $config['login_max_attempts'], $config['login_lockout_minutes'])) {
            $this->respond($request, false, 'Too many failed attempts. Please try again later.', null, '/login', 429);
            return;
        }

        $user = $this->auth->attempt($email, $password, $request->ip());
        if ($user === null) {
            $this->respond($request, false, 'Invalid email or password.', null, '/login');
            return;
        }

        Auth::login($user);
        Response::redirect(url('/dashboard'));
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Response::redirect(url('/login'));
    }

    public function showForgotPassword(Request $request): void
    {
        Response::view('auth/forgot-password', ['title' => 'Forgot password']);
    }

    public function forgotPassword(Request $request): void
    {
        $config = config('app');
        $email = (string) $request->input('email', '');
        $result = $this->auth->createPasswordReset($email, $config['password_reset_expiry_minutes']);
        if ($result !== null) {
            $link = url('/reset-password?token=' . $result['token']);
            (new \App\Services\LogMailer())->send($email, 'Reset your Split Pay password', "Reset your password: $link");
        }
        $this->respond($request, true, 'If that email exists, a password reset link has been sent.', null, '/login');
    }

    public function showResetPassword(Request $request): void
    {
        Response::view('auth/reset-password', ['title' => 'Reset password', 'token' => $request->query('token', '')]);
    }

    public function resetPassword(Request $request): void
    {
        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        if (strlen($password) < 8 || $password !== $confirm) {
            $this->respond($request, false, 'Passwords must match and be at least 8 characters.', null, '/reset-password?token=' . urlencode($token));
            return;
        }

        $ok = $this->auth->resetPassword($token, $password);
        $this->respond($request, $ok, $ok ? 'Your password has been reset. Please log in.' : 'This reset link is invalid or has expired.', null, '/login');
    }
}
