<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Language;
use App\Models\Locale;
use App\Models\PasswordResetToken;
use App\Models\RegistrationToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CriticalSecurityFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_token_is_single_use(): void
    {
        $user = User::factory()->create([
            'email' => 'person@example.com',
        ]);

        $plain = 'reset-token-for-testing';

        PasswordResetToken::query()->create([
            'email' => $user->email,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->post(route('password.update', ['token' => $plain]), [
            'password' => 'a-secure-new-password',
            'password_confirmation' => 'a-secure-new-password',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('a-secure-new-password', $user->fresh()->password));
        $this->assertNotNull(
            PasswordResetToken::query()->where('token_hash', hash('sha256', $plain))->value('used_at')
        );

        $this->post(route('password.update', ['token' => $plain]), [
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertStatus(410);
    }

    public function test_registration_token_is_consumed_when_account_is_created(): void
    {
        $country = Country::query()->create(['iso2' => 'BR', 'name' => 'Brasil']);
        $language = Language::query()->create(['code' => 'pt', 'name' => 'Português']);
        $plain = 'registration-token-for-testing';

        RegistrationToken::query()->create([
            'email' => 'new@example.com',
            'token_hash' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->post(route('register.complete.store', ['token' => $plain]), [
            'full_name' => 'New Translator',
            'country_id' => $country->id,
            'language_id' => $language->id,
            'password' => 'a-secure-new-password',
            'password_confirmation' => 'a-secure-new-password',
        ]);

        $response->assertRedirect(route('translations.index'));
        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
        $this->assertNotNull(
            RegistrationToken::query()->where('token_hash', hash('sha256', $plain))->value('used_at')
        );
    }

    public function test_translator_cannot_access_review_queue(): void
    {
        $locale = $this->makeLocale();
        $user = User::factory()->create(['locale_id' => $locale->id, 'email' => 'translator@example.com']);

        $this->actingAs($user)
            ->get(route('review.translations.index'))
            ->assertForbidden();
    }

    public function test_configured_reviewer_can_access_review_queue(): void
    {
        $locale = $this->makeLocale();
        $user = User::factory()->create(['locale_id' => $locale->id, 'email' => 'reviewer@example.com']);
        config(['torrent.reviewer_emails' => ['reviewer@example.com']]);

        $this->actingAs($user)
            ->get(route('review.translations.index'))
            ->assertOk();
    }

    private function makeLocale(): Locale
    {
        $country = Country::query()->create(['iso2' => 'BR', 'name' => 'Brasil']);
        $language = Language::query()->create(['code' => 'pt', 'name' => 'Português']);

        return Locale::query()->create([
            'country_id' => $country->id,
            'language_id' => $language->id,
            'code' => 'pt-BR',
            'name' => 'Português — Brasil',
            'active' => true,
        ]);
    }
}
