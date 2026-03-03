<?php

declare(strict_types=1);

namespace LaravelGlimpse\Contracts;

use Illuminate\Http\Request;

interface Resolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(Request $request): array;
}
