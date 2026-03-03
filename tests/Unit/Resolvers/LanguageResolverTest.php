<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use LaravelGlimpse\Resolvers\LanguageResolver;

it('returns null for empty Accept-Language header', function (mixed $language): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('Accept-Language', $language);

    $resolver = new LanguageResolver();
    $result = $resolver->resolve($request);

    expect($result)->toBe(['language' => null]);
})->with([
    null, '', '0',
]);

it('returns lowercase language for simple Accept-Language', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('Accept-Language', 'EN');

    $resolver = new LanguageResolver();
    $result = $resolver->resolve($request);

    expect($result)->toBe(['language' => 'en']);
});

it('returns primary language from Accept-Language with quality values', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('Accept-Language', 'en-US,en;q=0.9,fr;q=0.8');

    $resolver = new LanguageResolver();
    $result = $resolver->resolve($request);

    expect($result)->toBe(['language' => 'en-us']);
});

it('returns language with region in lowercase', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('Accept-Language', 'zh-TW');

    $resolver = new LanguageResolver();
    $result = $resolver->resolve($request);

    expect($result)->toBe(['language' => 'zh-tw']);
});

it('trims whitespace from language', function (): void {
    $request = Request::create('/', 'GET');
    $request->headers->set('Accept-Language', '  en-US  ');

    $resolver = new LanguageResolver();
    $result = $resolver->resolve($request);

    expect($result)->toBe(['language' => 'en-us']);
});
