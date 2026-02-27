<?php

declare(strict_types=1);

namespace LaravelGlimpse\Enums;

enum Platform: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Bot = 'bot';
}
