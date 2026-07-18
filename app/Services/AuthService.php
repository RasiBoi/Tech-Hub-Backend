<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Customer;
use App\Models\User;

class AuthService
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(array $data): array
    {
        $avatarColors = ['bg-blue-600 text-white', 'bg-indigo-600 text-white', 'bg-emerald-600 text-white', 'bg-amber-600 text-white', 'bg-rose-600 text-white'];
        $randomColor = $avatarColors[array_rand($avatarColors)];

        $role = $data['role'] ?? 'customer';
        $status = $role === 'vendor' ? 'pending' : 'approved';

        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $role,
            'store_name' => $role === 'vendor' ? ($data['store_name'] ?? null) : null,
            'avatar_bg' => $data['avatar_bg'] ?? $randomColor,
            'status' => $status,
        ]);
        $user->refresh();

        if ($role === 'customer') {
            Customer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id' => $user->ai_uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tier' => 'standard',
                ]
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
