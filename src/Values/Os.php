<?php

declare(strict_types=1);

namespace LaravelGlimpse\Values;

use Stringable;

final readonly class Os implements Stringable
{
    public string $icon;

    private function __construct(public string $name)
    {
        $this->icon = $this->normalizeIcon($name);
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public static function from(string $name): self
    {
        return new self($name);
    }

    private function normalizeIcon(string $name): string
    {
        $normalized = mb_strtolower($name);

        return match (true) {
            str_contains($normalized, 'windows') => 'windows',
            str_contains($normalized, 'mac') => 'mac',
            str_contains($normalized, 'android') => 'android',
            str_contains($normalized, 'linux') => 'linux',
            default => $normalized,
        };
    }
}
