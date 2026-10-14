<?php

namespace App\Http\Requests\Auth;

use App\Contracts\Repositories\UserRepositoryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ApiRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Email disamakan huruf kecilnya supaya A@x.com dan a@x.com tidak
     * menjadi dua akun.
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
            // Email unik hanya di antara akun yang belum dihapus, karena
            // users_email_aktif_unique juga parsial terhadap deleted_at.
            // Dibandingkan tanpa membedakan huruf besar dan kecil, supaya
            // akun lama yang tersimpan dengan huruf besar ikut terhitung.
            'email' => [
                'required', 'string', 'email', 'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (app(UserRepositoryInterface::class)->findByEmail((string) $value) !== null) {
                        $fail('Email sudah terdaftar.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
