<?php

declare(strict_types=1);

namespace LaravelGlimpse\Enums;

enum Period: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
