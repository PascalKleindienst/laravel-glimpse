<?php

declare(strict_types=1);

namespace LaravelGlimpse\Resolvers;

use hisorange\BrowserDetect\Contracts\ParserInterface;
use Illuminate\Http\Request;
use LaravelGlimpse\Contracts\Resolver;
use LaravelGlimpse\Enums\Platform;

final readonly class DeviceResolver implements Resolver
{
    public function __construct(private ParserInterface $browser) {}

    /**
     * @return array{browser: ?string, browser_version: ?string, os: ?string, os_version: ?string, platform: Platform, is_bot: bool}
     */
    public function resolve(Request $request): array
    {
        $ua = $request->userAgent() ?? '';

        if (empty($ua)) {
            return $this->unknown();
        }

        $result = $this->browser->parse($ua);

        $platform = match (true) {
            $result->isBot() => Platform::Bot,
            $result->isMobile() => Platform::Mobile,
            $result->isTablet() => Platform::Tablet,
            default => Platform::Desktop,
        };

        return [
            'browser' => $result->browserFamily() ?: null,
            'browser_version' => $result->browserVersion() ?: null,
            'os' => $result->platformName() ?: null,
            'os_version' => $result->platformVersion() ?: null,
            'platform' => $platform,
            'is_bot' => $result->isBot(),
        ];
    }

    public function isBot(Request $request): bool
    {
        return $this->browser->parse($request->userAgent() ?? '')->isBot();
    }

    /**
     * @return array{browser: ?string, browser_version: ?string, os: ?string, os_version: ?string, platform: Platform, is_bot: bool}
     */
    private function unknown(): array
    {
        return [
            'browser' => null,
            'browser_version' => null,
            'os' => null,
            'os_version' => null,
            'platform' => Platform::Desktop,
            'is_bot' => false,
        ];
    }
}
