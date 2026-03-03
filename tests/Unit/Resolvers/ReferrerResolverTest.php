<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use LaravelGlimpse\Enums\ReferrerChannel;
use LaravelGlimpse\Resolvers\ReferrerResolver;

beforeEach(function (): void {
    config(['app.url' => 'https://example.com']);
    config(['glimpse.resolver.'.ReferrerResolver::class.'.search_engines' => ['google', 'bing', 'yahoo']]);
    config(['glimpse.resolver.'.ReferrerResolver::class.'.social_media' => ['facebook', 'twitter', 'linkedin']]);
    config(['glimpse.resolver.'.ReferrerResolver::class.'.email_clients' => ['mail.ru', 'outlook']]);
    config(['glimpse.resolver.'.ReferrerResolver::class.'.paid_params' => ['gclid', 'fbclid']]);

    $this->resolver = new ReferrerResolver();
});

it('returns direct channel for missing referrer header', function (mixed $referrer): void {
    $request = Request::create('/', 'GET');
    if ($referrer !== null) {
        $request->headers->set('referer', $referrer);
    }

    $result = $this->resolver->resolve($request);

    expect($result)->toBe([
        'referrer_url' => null,
        'referrer_domain' => null,
        'referrer_channel' => ReferrerChannel::Direct,
    ]);
})->with([null, '', '0']);

it('returns internal channel for same host referrer', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', 'https://example.com/page');

    $result = $this->resolver->resolve($request);

    expect($result)->toBe([
        'referrer_url' => null,
        'referrer_domain' => null,
        'referrer_channel' => ReferrerChannel::Internal,
    ]);
});

it('returns internal channel for subdomain referrer', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', 'https://blog.example.com/page');

    $result = $this->resolver->resolve($request);

    expect($result)->toBe([
        'referrer_url' => null,
        'referrer_domain' => null,
        'referrer_channel' => ReferrerChannel::Internal,
    ]);
});

it('returns organic channel for search engine referrer', function (string $referrer): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', $referrer);

    $result = $this->resolver->resolve($request);

    expect($result['referrer_channel'])->toBe(ReferrerChannel::Organic);
})->with([
    'https://www.google.com/search?q=test',
    'https://www.google.de/search?q=test',
    'https://www.bing.com/search?q=test',
    'https://search.yahoo.com/search?p=test',
]);

it('extracts root domain correctly', function (string $referrer, string $domain): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', $referrer);

    $result = $this->resolver->resolve($request);
    expect($result['referrer_domain'])->toBe($domain);
})->with([
    ['https://www.google.co.uk/search?q=test', 'google.co.uk'],
    ['https://www.google.de/search?q=test', 'google.de'],
    ['https://google.co.uk/search?q=test', 'google.co.uk'],
    ['https://google.de/search?q=test', 'google.de'],
    ['https://amazon.com.au', 'amazon.com.au'],
]);

it('returns social channel for social network referrer', function (string $referrer): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', $referrer);

    $result = $this->resolver->resolve($request);

    expect($result['referrer_channel'])->toBe(ReferrerChannel::Social);
})->with([
    'https://www.facebook.com/profile',
    'https://twitter.com/user',
    'https://www.linkedin.com/in/user',
]);

it('returns email channel for email client referrer', function (string $referrer): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', $referrer);

    $result = $this->resolver->resolve($request);

    expect($result['referrer_channel'])->toBe(ReferrerChannel::Email);
})->with([
    'https://mail.ru/inbox',
    'https://outlook.com/inbox',
]);

it('returns right channel for query param', function (string $param, ReferrerChannel $channel): void {
    $request = Request::create('/'.$param, 'GET');
    $request->headers->set('referer', 'https://external-site.com');

    $result = $this->resolver->resolve($request);

    expect($result['referrer_channel'])->toBe($channel);
})->with([
    ['?utm_medium=cpc', ReferrerChannel::Paid],
    ['?utm_medium=paid', ReferrerChannel::Paid],
    ['?utm_medium=email', ReferrerChannel::Email],
    ['?utm_medium=paid', ReferrerChannel::Paid],
    ['?gclid=test123', ReferrerChannel::Paid],
    ['?fbclid=test123', ReferrerChannel::Paid],
]);

it('returns referral channel for unknown external referrer', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', 'https://unknown-site.com/page');

    $result = $this->resolver->resolve($request);

    expect($result)->toBe([
        'referrer_url' => 'https://unknown-site.com/page',
        'referrer_domain' => 'unknown-site.com',
        'referrer_channel' => ReferrerChannel::Referral,
    ]);
});

it('extracts root domain for multi-level subdomain', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', 'https://sub.sub.external.com/page');

    $result = $this->resolver->resolve($request);

    expect($result['referrer_domain'])->toBe('external.com');
});

it('handles referrer without scheme', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('referer', 'https://www.google.com/search');

    $result = $this->resolver->resolve($request);

    expect($result['referrer_channel'])->toBe(ReferrerChannel::Organic)
        ->and($result['referrer_domain'])->toBe('google.com');
});
