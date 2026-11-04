<?php

namespace Tests\Unit\MarketTrust;

use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuthenticationRulesTest extends TestCase
{
    /**
     * TC-01-02-U01: Aturan format regex username hanya boleh huruf kecil, angka, dan underscore
     */
    public function test_tc_01_02_username_format_rule_validation(): void
    {
        $rules = (new RegisterRequest)->rules()['username'];
        // Ambil aturan murni non-database untuk unit test
        $formatRules = array_filter($rules, fn ($rule) => ! is_string($rule) || ! str_starts_with($rule, 'unique:'));

        // Valid username format
        $validValidator = Validator::make(['username' => 'john_doe123'], ['username' => $formatRules]);
        $this->assertFalse($validValidator->fails());

        // Invalid username format (spasi atau karakter khusus)
        $invalidValidator = Validator::make(['username' => 'john doe!'], ['username' => $formatRules]);
        $this->assertTrue($invalidValidator->fails());
        $this->assertArrayHasKey('username', $invalidValidator->errors()->toArray());
    }

    /**
     * TC-01-02-U02: Aturan batas panjang username (min 3, max 30)
     */
    public function test_tc_01_02_username_length_boundaries(): void
    {
        $rules = (new RegisterRequest)->rules()['username'];
        $formatRules = array_filter($rules, fn ($rule) => ! is_string($rule) || ! str_starts_with($rule, 'unique:'));

        // Kurang dari 3 karakter
        $shortValidator = Validator::make(['username' => 'ab'], ['username' => $formatRules]);
        $this->assertTrue($shortValidator->fails());

        // Lebih dari 30 karakter
        $longValidator = Validator::make(['username' => str_repeat('a', 31)], ['username' => $formatRules]);
        $this->assertTrue($longValidator->fails());
    }

    /**
     * TC-01-02-U03: Aturan password minimal 8 karakter dan konfirmasi kecocokan
     */
    public function test_tc_01_02_password_length_and_confirmation_rules(): void
    {
        $rules = (new RegisterRequest)->rules()['password'];

        // Password kurang dari 8 karakter
        $shortPasswordValidator = Validator::make(
            ['password' => 'short', 'password_confirmation' => 'short'],
            ['password' => $rules]
        );
        $this->assertTrue($shortPasswordValidator->fails());

        // Password tidak cocok dengan konfirmasi
        $mismatchValidator = Validator::make(
            ['password' => 'validpassword123', 'password_confirmation' => 'different123'],
            ['password' => $rules]
        );
        $this->assertTrue($mismatchValidator->fails());
    }
}
