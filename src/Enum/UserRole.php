<?php

namespace App\Enum;

enum UserRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::USER => 'Regular User',
            self::ADMIN => 'Administrator',
        };
    }

    public function value(): string
    {
        return $this->value;
    }
}