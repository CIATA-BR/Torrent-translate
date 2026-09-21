<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(
        protected MicrosoftGraphMailService $mailService,
        protected AuditTrail $audit
    ) {}

    public function requestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower(trim($data['email']));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $plain = Str::random(64);

            PasswordResetToken::query()
                ->where('email', $user->email)
                ->whereNull('used_at')
                ->delete();

            PasswordResetToken::query()->create([
                'email' => $user->email,
                'token_hash' => hash('sha256', $plain),
                'expires_at' => now()->addMinutes(30),
            ]);

            $url = route('password.reset', ['token' => $plain]);

            try {
                $this->mailService->sendMail(
                    to: $user->email,
                    subject: 'Redefinição de senha - Torrent Translate',
                    htmlBody: '<h1>Redefinição de senha</h1>'
                        .'<p>Foi solicitada uma nova senha para a conta do Torrent Translate.</p>'
                        .'<p><a href="'.e($url).'">Redefinir senha</a></p>'
                        .'<p>Este link expira em 30 minutos e só pode ser usado uma vez.</p>'
                        .'<p>Se esta solicitação não foi feita por você, ignore este e-mail.</p>'
                );

                $this->audit->record(
                    'auth.password_reset_requested',
                    null,
                    User::class,
                    $user->id
                );
            } catch (\Throwable $e) {
                Log::error('Falha ao enviar link de recuperação de senha.', [
                    'user_id' => $user->id,
                    'exception_class' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('password.request')
            ->with('password_reset_email_sent', true);
    }

    public function resetForm(string $token)
    {
        $record = $this->validToken($token);
        abort_unless($record, 410, __('portal.password_reset_invalid'));

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $record->email,
        ]);
    }

    public function reset(Request $request, string $token)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:12'],
        ]);

        $tokenHash = hash('sha256', $token);

        $user = DB::transaction(function () use ($data, $tokenHash): User {
            $record = PasswordResetToken::query()
                ->where('token_hash', $tokenHash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            abort_unless($record, 410, __('portal.password_reset_invalid'));

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($record->email)])
                ->lockForUpdate()
                ->first();

            abort_unless($user, 410, __('portal.password_reset_invalid'));

            $user->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();

            PasswordResetToken::query()
                ->where('email', $record->email)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return $user;
        });

        $this->audit->record(
            'auth.password_reset_completed',
            $user,
            User::class,
            $user->id
        );

        return redirect()
            ->route('login')
            ->with('status', __('portal.password_reset_complete'));
    }

    private function validToken(string $plain): ?PasswordResetToken
    {
        return PasswordResetToken::query()
            ->where('token_hash', hash('sha256', $plain))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
