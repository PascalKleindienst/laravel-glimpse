<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use LaravelGlimpse\GlimpseGate;

beforeEach(function (): void {
    GlimpseGate::reset();
});

it('denies access when no user is present', function (): void {
    $request = Request::create('/');

    expect(GlimpseGate::check($request))->toBeFalse();
});

it('denies access when user is not authenticatable', function (): void {
    $request = Request::create('/');
    $request->setUserResolver(fn (): object => new class {});

    expect(GlimpseGate::check($request))->toBeFalse();
});

it('allows access when user is authenticatable', function (): void {
    $request = Request::create('/');
    $request->setUserResolver(fn (): Authenticatable => new class implements Authenticatable
    {
        public int $id = 1;

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): mixed
        {
            return $this->id;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return 'hashed';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    });

    expect(GlimpseGate::check($request))->toBeTrue();
});

it('uses custom callback when registered', function (): void {
    $request = Request::create('/');

    GlimpseGate::using(static fn (Request $request): true => true);
    expect(GlimpseGate::check($request))->toBeTrue();

    GlimpseGate::using(static fn (Request $request): false => false);
    expect(GlimpseGate::check($request))->toBeFalse();
});

it('passes request to custom callback', function (): void {
    $request = Request::create('/glimpse');
    $passedRequest = null;

    GlimpseGate::using(static function (Request $req) use (&$passedRequest): bool {
        $passedRequest = $req;

        return true;
    });

    GlimpseGate::check($request);

    expect($passedRequest)->toBe($request);
});

it('reset restores default behavior', function (): void {
    $request = Request::create('/');

    GlimpseGate::using(static fn (): true => true);
    expect(GlimpseGate::check($request))->toBeTrue();

    GlimpseGate::reset();
    expect(GlimpseGate::check($request))->toBeFalse();
});

it('ignores user when custom callback is registered', function (): void {
    $request = Request::create('/');
    $request->setUserResolver(fn () => mock(Authenticatable::class));

    GlimpseGate::using(static fn (): false => false);
    expect(GlimpseGate::check($request))->toBeFalse();
});
