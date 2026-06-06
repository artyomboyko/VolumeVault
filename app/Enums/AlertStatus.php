<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Active = 'active';
    case Resolved = 'resolved';
}
