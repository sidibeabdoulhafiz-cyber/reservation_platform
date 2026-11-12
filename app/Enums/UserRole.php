<?php

namespace App\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Controleur = 'controleur';
    case Admin = 'admin';
}
