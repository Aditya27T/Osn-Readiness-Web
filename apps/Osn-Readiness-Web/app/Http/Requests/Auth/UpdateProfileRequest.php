<?php

namespace App\Http\Requests\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Email disamakan huruf kecilnya supaya A@x.com dan a@x.com tidak
     * menjadi dua akun (sama seperti register/login).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Email unik di antara akun yang belum dihapus (case-insensitive,
            // sama seperti register), kecuali milik sendiri.
            'email' => [
                'required', 'string', 'email', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $pemilik = app(UserRepositoryInterface::class)->findByEmail((string) $value);

                    if ($pemilik !== null && $pemilik->getKey() !== $this->user()?->getKey()) {
                        $fail('Email sudah terdaftar.');
                    }
                },
            ],
        ];
    }
}
