<?php

declare(strict_types=1);

namespace LaravelGlimpse\Enums;

enum ReferrerChannel: string
{
    case Direct = 'direct';
    case Internal = 'internal';
    case Organic = 'organic';
    case Social = 'social';
    case Email = 'email';
    case Paid = 'paid';
    case Referral = 'referral';
    case Other = 'other';
}
