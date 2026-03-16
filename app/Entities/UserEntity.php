<?php

namespace App\Entities;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserEntity
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public static function make(User $user): self
    {
        return new self($user);
    }

    public static function findByEmail(string $email): ?self
    {
        $user = User::where('email', $email)->first();
        return $user ? self::make($user) : null;
    }

    public function getPassword(): string
    {
        return $this->user->password;
    }

    public function getId(): int
    {
        return $this->user->id;
    }

    public function getEmail(): string
    {
        return $this->user->email;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
