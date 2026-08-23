<?php

declare(strict_types=1);

namespace Veldora\Framework\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Veldora\Framework\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ];
    }
}

class FormRequestTest extends TestCase
{
    public function test_form_request_validated_data(): void
    {
        $request = new StoreUserRequest(
            ['name' => 'Alice', 'email' => 'alice@example.com', 'extra' => 'val'],
            ['REQUEST_METHOD' => 'POST']
        );

        $this->assertTrue($request->authorize());
        $request->validateResolved();

        $validated = $request->validated();
        $this->assertSame('Alice', $validated['name']);
        $this->assertSame('alice@example.com', $validated['email']);
        $this->assertSame('Alice', $request->validated('name'));
    }
}
