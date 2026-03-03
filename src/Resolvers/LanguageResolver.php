<?php

declare(strict_types=1);

namespace LaravelGlimpse\Resolvers;

use Illuminate\Http\Request;
use LaravelGlimpse\Contracts\Resolver;

use function in_array;

final readonly class LanguageResolver implements Resolver
{
    /**
     * Resolve language from the Accept-Language header.
     * Returns the primary language tag, e.g. "en", "fr", "zh-TW"
     *
     * @return array{language: ?string}
     */
    public function resolve(Request $request): array
    {
        $header = $request->headers->get('Accept-Language', '');

        if (in_array($header, [null, '', '0'], true)) {
            return ['language' => null];
        }

        // "en-US,en;q=0.9,fr;q=0.8" → "en-US"
        $primary = explode(',', $header)[0];
        $primary = explode(';', $primary)[0];

        return ['language' => mb_strtolower(mb_trim($primary)) ?: null];
    }
}
