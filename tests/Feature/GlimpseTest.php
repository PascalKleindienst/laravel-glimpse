<?php

declare(strict_types=1);

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Event;
use LaravelGlimpse\Events\GlimpseEventDispatchedEvent;
use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Facades\Glimpse as GlimpseFacade;
use LaravelGlimpse\Glimpse;
use LaravelGlimpse\Services\QueryService;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('can be instantiated via container', function (): void {
    $glimpse = resolve(Glimpse::class);

    expect($glimpse)->toBeInstanceOf(Glimpse::class);
});

it('can be accessed via facade', function (): void {
    expect(GlimpseFacade::getFacadeRoot())->toBeInstanceOf(Glimpse::class);
});

describe('event()', function (): void {
    it('dispatches event with name and properties', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->event('signup', ['plan' => 'pro']);

        assertDatabaseHas('glimpse_events', [
            'name' => 'signup',
            'properties' => json_encode(['plan' => 'pro'], JSON_THROW_ON_ERROR),
        ]);
    });

    it('dispatches event with empty properties', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->event('checkout');

        assertDatabaseHas('glimpse_events', [
            'name' => 'checkout',
            'properties' => null,
        ]);
    });

    it('dispatches event with custom session hash', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->event('custom_event', [], 'custom-session-hash');

        assertDatabaseHas('glimpse_events', [
            'name' => 'custom_event',
            'session_hash' => 'custom-session-hash',
        ]);
    });

    it('dispatches multiple events', function (): void {
        $glimpse = resolve(Glimpse::class);

        $glimpse->event('event_1', ['step' => 1]);
        $glimpse->event('event_2', ['step' => 2]);
        $glimpse->event('event_3', ['step' => 3]);

        assertDatabaseCount('glimpse_events', 3);
    });

    it('throws exception for invalid event name', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->event('invalid name!');
    })->throws(InvalidEventNameException::class);

    it('dispatches Laravel event after creating event', function (): void {
        Event::listen(GlimpseEventDispatchedEvent::class, function ($event): void {
            expect($event->name)->toBe('test_event')
                ->and($event->payload)->toBe(['key' => 'value'])
                ->and($event->sessionHash)->toBe('test-hash');
        });

        $glimpse = resolve(Glimpse::class);
        $glimpse->event('test_event', ['key' => 'value'], 'test-hash');
    });

    it('does not dispatch event when glimpse is disabled', function (): void {
        config(['glimpse.enabled' => false]);

        $glimpse = resolve(Glimpse::class);
        $glimpse->event('disabled_event');

        assertDatabaseCount('glimpse_events', 0);
    });
});

describe('dispatchSilently()', function (): void {
    it('does not throw for invalid event name', function (): void {
        $glimpse = resolve(Glimpse::class);

        $glimpse->dispatchSilently('invalid name!');
    })->throwsNoExceptions();

    it('does not throw for empty event name', function (): void {
        $glimpse = resolve(Glimpse::class);

        $glimpse->dispatchSilently('');
    })->throwsNoExceptions();

    it('creates event for valid names', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->dispatchSilently('silent_event', ['source' => 'test']);

        assertDatabaseHas('glimpse_events', [
            'name' => 'silent_event',
            'properties' => json_encode(['source' => 'test'], JSON_THROW_ON_ERROR),
        ]);
    });

    it('does not create event for invalid names', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->dispatchSilently('invalid name!');

        assertDatabaseCount('glimpse_events', 0);
    });

    it('does not dispatch event when glimpse is disabled', function (): void {
        config(['glimpse.enabled' => false]);

        $glimpse = resolve(Glimpse::class);
        $glimpse->dispatchSilently('any_event');

        assertDatabaseCount('glimpse_events', 0);
    });
});

describe('page()', function (): void {
    it('dispatches page_view event with path', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->page('/checkout');

        assertDatabaseHas('glimpse_events', [
            'name' => 'page_view',
            'properties' => json_encode(['path' => '/checkout'], JSON_THROW_ON_ERROR),
        ]);
    });

    it('dispatches page_view event with path and title', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->page('/checkout', 'Checkout - Step 2');

        assertDatabaseHas('glimpse_events', [
            'name' => 'page_view',
            'properties' => json_encode([
                'path' => '/checkout',
                'title' => 'Checkout - Step 2',
            ], JSON_THROW_ON_ERROR),
        ]);
    });

    it('does not include title in properties when null', function (): void {
        $glimpse = resolve(Glimpse::class);
        $glimpse->page('/about');

        assertDatabaseHas('glimpse_events', [
            'name' => 'page_view',
            'properties' => json_encode(['path' => '/about'], JSON_THROW_ON_ERROR),
        ]);
    });
});

describe('query()', function (): void {
    it('returns the QueryService instance', function (): void {
        $glimpse = resolve(Glimpse::class);

        expect($glimpse->query())->toBeInstanceOf(QueryService::class);
    });
});

describe('currentSessionHash()', function (): void {
    it('returns null when no session exists', function (): void {
        $glimpse = resolve(Glimpse::class);

        expect($glimpse->currentSessionHash())->toBeNull();
    });

    it('returns null when session is not started yed', function (): void {
        request()->setLaravelSession(resolve(Session::class));

        $glimpse = resolve(Glimpse::class);

        expect($glimpse->currentSessionHash())->toBeNull();
    });

    it('returns session hash when session exists', function (): void {
        request()->setLaravelSession(resolve(Session::class));
        request()->session()->start();

        $glimpse = resolve(Glimpse::class);

        expect($glimpse->currentSessionHash())->toBeString();
    });
});

describe('facade proxy', function (): void {
    it('dispatches event through facade', function (): void {
        GlimpseFacade::event('facade_event', ['via' => 'facade']);

        assertDatabaseHas('glimpse_events', [
            'name' => 'facade_event',
            'properties' => json_encode(['via' => 'facade'], JSON_THROW_ON_ERROR),
        ]);
    });

    it('dispatches page through facade', function (): void {
        GlimpseFacade::page('/test-page', 'Test Page');

        assertDatabaseHas('glimpse_events', [
            'name' => 'page_view',
            'properties' => json_encode([
                'path' => '/test-page',
                'title' => 'Test Page',
            ], JSON_THROW_ON_ERROR),
        ]);
    });

    it('dispatches silently through facade', function (): void {
        GlimpseFacade::dispatchSilently('invalid name!');

        assertDatabaseCount('glimpse_events', 0);
    });

    it('accesses query through facade', function (): void {
        expect(GlimpseFacade::query())->toBeInstanceOf(QueryService::class);
    });
});
