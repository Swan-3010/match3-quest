<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'username' => explode('@', $data['email'])[0],
        ]);

        UserProgress::create([
            'user_id' => $user->id,
            'lives' => 5,
            'coins' => 500,
            'gems' => 10,
            'last_life_regen_at' => now(),
        ]);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages(['email' => 'Неверные credentials']);
        }

        $token = $user->createToken('auth')->plainTextToken;
        $refreshToken = Str::random(64);

        return response()->json([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $token = Str::random(64);
            PasswordReset::create([
                'user_id' => $user->id,
                'token' => Hash::make($token),
                'expires_at' => now()->addHour(),
            ]);
            // TODO: отправить письмо с токеном
        }

        return response()->json(['message' => 'Если email существует, письмо отправлено']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) return response()->json(['error' => 'Неверный токен'], 400);

        $reset = PasswordReset::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$reset || !Hash::check($data['token'], $reset->token)) {
            return response()->json(['error' => 'Неверный или просроченный токен'], 400);
        }

        $user->update(['password_hash' => Hash::make($data['password'])]);
        $reset->update(['used' => true]);

        return response()->json(['message' => 'Пароль изменён']);
    }
}