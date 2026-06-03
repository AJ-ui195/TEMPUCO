<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
    case Cashier = 'cashier';
    case Inventory = 'inventory';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::User => 'User',
            self::Cashier => __('Cashier'),
            self::Inventory => __('Inventory'),
        };
    }
}
