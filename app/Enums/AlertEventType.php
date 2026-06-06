<?php

namespace App\Enums;

enum AlertEventType: string
{
    case Triggered = 'triggered';
    case Resolved = 'resolved';
    case Notified = 'notified';
    case ReminderSent = 'reminder_sent';
}
