<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Auth\CompleteGoogleRegisterRequest;
use App\Http\Requests\Auth\LinkGoogleAccountRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class GoogleOAuthProfileRulesTest extends TestCase
{
    /**
     * TC-02-05-U01 (Turunan): Algoritma sanitasi nama Google menjadi username yang sah
     */
    public function test_tc_02_05_google_name_sanitization_for_username(): void
    {
        $rawName = 'John Doe & Friends! 2026';
        $cleanName = Str::lower(preg_replace('/[^a-z0-9_]/', '', Str::slug($rawName, '_')));
        $suggestedUsername = substr($cleanName ?: 'user', 0, 20);

        $this->assertEquals('john_doe_friends_202', $suggestedUsername);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $suggestedUsername);
        $this->assertLessThanOrEqual(20, strlen($suggestedUsername));
    }

    /**
     * TC-02-05-U02 (Turunan): Aturan validasi username form registrasi Google OAuth
     */
    public function test_tc_02_05_complete_google_register_rules(): void
    {
        $rules = (new CompleteGoogleRegisterRequest)->rules()['username'];
        $formatRules = array_filter($rules, fn ($rule) => ! is_string($rule) || ! str_starts_with($rule, 'unique:'));

        $validValidator = Validator::make(['username' => 'google_user_99'], ['username' => $formatRules]);
        $this->assertFalse($validValidator->fails());

        $invalidValidator = Validator::make(['username' => 'invalid username!'], ['username' => $formatRules]);
        $this->assertTrue($invalidValidator->fails());
    }

    /**
     * TC-02-04-U01 (Turunan): Aturan penautan akun Google lokal mewajibkan verifikasi password
     */
    public function test_tc_02_04_link_account_password_validation(): void
    {
        $rules = (new LinkGoogleAccountRequest)->rules();

        $this->assertArrayHasKey('password', $rules);
        $emptyValidator = Validator::make([], $rules);
        $this->assertTrue($emptyValidator->fails());
        $this->assertArrayHasKey('password', $emptyValidator->errors()->toArray());
    }
}
