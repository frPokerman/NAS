<?php

namespace App\Enum\Config;

enum PasswordDisplay: string
{
    case Always = 'Always displayed';
    case Action = 'Need action';
    case Master = 'Require master password';
    case External = 'Require external authentification';
}