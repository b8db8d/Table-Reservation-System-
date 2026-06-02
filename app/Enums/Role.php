<?php

namespace App\Enums;

enum Role: string
{
    case Manager = 'manager';
    case Staff = 'staff';
    case Customer = 'customer';
}
