<?php

declare(strict_types=1);

namespace LaravelGlimpse\Resolvers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LaravelGlimpse\Contracts\Resolver;
use LaravelGlimpse\Enums\ReferrerChannel;

use function count;
use function in_array;

final readonly class ReferrerResolver implements Resolver
{
    /**
     * @var string[]
     */
    private array $searchEngines;

    /**
     * @var string[]
     */
    private array $socialNetworks;

    /**
     * @var string[]
     */
    private array $emailClients;

    /**
     * @var string[]
     */
    private array $paidParams;

    public function __construct()
    {
        $this->searchEngines = config('glimpse.resolver.'.self::class.'.search_engines', []);
        $this->socialNetworks = config('glimpse.resolver.'.self::class.'.social_media', []);
        $this->emailClients = config('glimpse.resolver.'.self::class.'.email_clients', []);
        $this->paidParams = config('glimpse.resolver.'.self::class.'.paid_params', []);
    }

    /**
     * @return array{referrer_url: ?string, referrer_domain: ?string, referrer_channel: ReferrerChannel}
     */
    public function resolve(Request $request): array
    {
        $referrer = $request->headers->get('referer', '');
        $appHost = parse_url((string) config('app.url', ''), PHP_URL_HOST) ?? '';

        // No referrer header at all → direct
        if (in_array($referrer, [null, '', '0'], true)) {
            return $this->result(null, null, ReferrerChannel::Direct);
        }

        $parts = parse_url($referrer);
        $host = mb_strtolower($parts['host'] ?? '');
        $domain = $this->extractRootDomain($host);

        // Internal navigation — don't record as a referrer.
        if ($host === $appHost || str_ends_with($host, ".{$appHost}")) {
            return $this->result(null, null, ReferrerChannel::Internal);
        }

        $channel = $this->classifyChannel($domain, $request);

        return $this->result($referrer, $domain, $channel);
    }

    /**
     * @return array{referrer_url: ?string, referrer_domain: ?string, referrer_channel: ReferrerChannel}
     */
    private function result(?string $url, ?string $domain, ReferrerChannel $channel): array
    {
        return [
            'referrer_url' => $url,
            'referrer_domain' => $domain,
            'referrer_channel' => $channel,
        ];
    }

    /**
     * Strip subdomains: 'www.google.co.uk' → 'google.co.uk'
     */
    private function extractRootDomain(string $host): string
    {
        $result = preg_replace('/^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/\n]+)/', '$1', $host) ?? $host;

        // Fallback
        if ($result === $host && ! Str::contains($host, ['.co.uk', '.co.nz', '.co.au', '.com.uk', '.com.nz', '.com.au'])) {
            $parts = explode('.', $host);
            $count = count($parts);

            if ($count > 2) {
                return $parts[$count - 2].'.'.$parts[$count - 1];
            }
        }

        return $result;
    }

    private function classifyChannel(string $domain, Request $request): ReferrerChannel
    {
        // Paid traffic signals take priority (UTM / click IDs)
        if ($this->hasPaidParams($request)) {
            return ReferrerChannel::Paid;
        }

        if ($request->query('utm_medium') === 'email') {
            return ReferrerChannel::Email;
        }

        if ($this->matchesList($domain, $this->searchEngines)) {
            return ReferrerChannel::Organic;
        }

        if ($this->matchesList($domain, $this->socialNetworks)) {
            return ReferrerChannel::Social;
        }

        if ($this->matchesList($domain, $this->emailClients)) {
            return ReferrerChannel::Email;
        }

        return ReferrerChannel::Referral;
    }

    private function hasPaidParams(Request $request): bool
    {
        foreach ($this->paidParams as $param) {
            if ($request->has($param)) {
                return true;
            }
        }
        if ($request->query('utm_medium') === 'cpc') {
            return true;
        }

        return $request->query('utm_medium') === 'paid';
    }

    /**
     * @param  string[]  $list
     */
    private function matchesList(string $domain, array $list): bool
    {
        return array_any($list, fn (string $entry): bool => str_contains($domain, $entry));
    }
}
