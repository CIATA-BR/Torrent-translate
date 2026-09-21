<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\AuditTrail;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Http\Request;
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

        return back()->with('status', __('portal.password_reset_link_sent'));
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
        $record = $this->validToken($token);
        abort_unless($record, 410, __('portal.password_reset_invalid'));

        $data = $request->validate([
            'password' => ['required', 'confirmed', 'min:12'],
        ]);

        $user = User::query()->where('email', $record->email)->first();
        abort_unless($user, 410, __('portal.password_reset_invalid'));

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        $record->update(['used_at' => now()]);

        PasswordResetToken::query()
            ->where('email', $record->email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

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
