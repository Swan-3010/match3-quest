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
            'secret_question' => 'nullable|string|max:255',
            'secret_answer' => 'nullable|string|max:255|required_with:secret_question',
        ]);

        $user = User::create([
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'username' => explode('@', $data['email'])[0],
            'secret_question' => $data['secret_question'] ?? null,
            'secret_answer_hash' => !empty($data['secret_answer'])
                ? Hash::make(mb_strtolower(trim($data['secret_answer'])))
                : null,
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
            'remember' => 'nullable|boolean',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password_hash)) {
            throw ValidationException::withMessages(['email' => 'Неверные credentials']);
        }

        $expiresAt = $request->boolean('remember') ? now()->addDays(30) : now()->addHours(12);
        $token = $user->createToken('auth', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'token' => $token,
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

    public function recoverQuestion(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !$user->secret_question) {
            // Единый ответ, чтобы не светить существование email
            return response()->json(['error' => 'Восстановление по секретному вопросу недоступно'], 400);
        }

        return response()->json(['question' => $user->secret_question]);
    }

    public function recoverReset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'answer' => 'required|string|max:255',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !$user->secret_answer_hash) {
            return response()->json(['error' => 'Неверный ответ'], 400);
        }

        if (!Hash::check(mb_strtolower(trim($data['answer'])), $user->secret_answer_hash)) {
            return response()->json(['error' => 'Неверный ответ'], 400);
        }

        $user->update(['password_hash' => Hash::make($data['password'])]);
        $user->tokens()->delete(); // все устройства перелогинятся

        return response()->json(['message' => 'Пароль изменён. Войдите с новым паролем.']);
    }
}