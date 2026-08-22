<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Validation\Validator;
use Veldora\Framework\Validation\ValidationException;
use Veldora\Framework\Validation\Rule;

class ValidationTest extends TestCase
{
    public function test_it_validates_required_fields(): void
    {
        $rules = [
            'name'  => 'required',
            'email' => 'required',
        ];

        $data = ['name' => 'Veldora'];

        $validator = new Validator($data, $rules);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors());
    }

    public function test_it_validates_email_format(): void
    {
        $rules = ['email' => 'email'];

        $validatorPass = new Validator(['email' => 'tech@veldora.dev'], $rules);
        $this->assertFalse($validatorPass->fails());

        $validatorFail = new Validator(['email' => 'invalid-email-address'], $rules);
        $this->assertTrue($validatorFail->fails());
    }

    public function test_it_validates_min_max_and_between_ranges(): void
    {
        $rules = [
            'username' => 'min:3|max:10',
            'age'      => 'between:18,99',
        ];

        $validatorPass = new Validator([
            'username' => 'veldora',
            'age'      => 25
        ], $rules);
        $this->assertFalse($validatorPass->fails());

        $validatorFail = new Validator([
            'username' => 've', // too short
            'age'      => 10  // too low
        ], $rules);
        $this->assertTrue($validatorFail->fails());
        $this->assertArrayHasKey('username', $validatorFail->errors());
        $this->assertArrayHasKey('age', $validatorFail->errors());
    }

    public function test_it_validates_confirmation_and_same(): void
    {
        $rules = [
            'password' => 'confirmed',
            'email'    => 'same:email_confirm',
        ];

        $validatorFail = new Validator([
            'password'      => 'secret123',
            'password_confirmation' => 'different',
            'email'         => 'a@b.com',
            'email_confirm' => 'x@y.com'
        ], $rules);
        $this->assertTrue($validatorFail->fails());

        $validatorPass = new Validator([
            'password'      => 'secret123',
            'password_confirmation' => 'secret123',
            'email'         => 'a@b.com',
            'email_confirm' => 'a@b.com'
        ], $rules);
        $this->assertFalse($validatorPass->fails());
    }

    public function test_it_supports_custom_rule_classes(): void
    {
        $customRule = new class implements Rule {
            public function passes(string $attribute, mixed $value): bool {
                return $value === 'veldora';
            }
            public function message(): string {
                return 'Value must be veldora.';
            }
        };

        $rules = ['framework' => [$customRule]];

        $validatorPass = new Validator(['framework' => 'veldora'], $rules);
        $this->assertFalse($validatorPass->fails());

        $validatorFail = new Validator(['framework' => 'other'], $rules);
        $this->assertTrue($validatorFail->fails());
        $this->assertSame('Value must be veldora.', $validatorFail->errors()['framework'][0]);
    }
}
