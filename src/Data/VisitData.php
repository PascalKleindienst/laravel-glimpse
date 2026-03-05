<?php

declare(strict_types=1);

namespace LaravelGlimpse\Data;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use LaravelGlimpse\Facades\SessionTrackerService;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class VisitData implements Arrayable
{
    public function __construct(
        public string $sessionHash,
        public string $ipHash,
        public string $fullUrl,
        public string $path,
        public ?string $queryString,
        public ?string $userAgent,
        public ?string $referer,
        public ?string $acceptLanguage,
        public string $ip,
        public CarbonImmutable $hitAt,
        public bool $isNewSession,
    ) {}

    public static function from(Request $request): self
    {
        $sessionHash = SessionTrackerService::resolveSessionHash($request);

        return new self(
            sessionHash: $sessionHash,
            ipHash: SessionTrackerService::hashIp($request->ip() ?? '-'),
            fullUrl: $request->fullUrl(),
            path: '/'.mb_ltrim($request->path(), '/'),
            queryString: $request->getQueryString(),
            userAgent: $request->userAgent(),
            referer: $request->headers->get('referer'),
            acceptLanguage: $request->headers->get('Accept-Language'),
            ip: $request->ip() ?? '-',
            hitAt: CarbonImmutable::now(),
            isNewSession: ! SessionTrackerService::isActive($sessionHash),
        );
    }

    public function toRequest(): Request
    {
        return Request::create(
            $this->fullUrl,
            'GET',
            [],
            [],
            [],
            array_filter([
                'HTTP_USER_AGENT' => $this->userAgent,
                'HTTP_REFERER' => $this->referer,
                'HTTP_ACCEPT_LANGUAGE' => $this->acceptLanguage,
                'REMOTE_ADDR' => $this->ip,
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sessionHash' => $this->sessionHash,
            'ipHash' => $this->ipHash,
            'fullUrl' => $this->fullUrl,
            'path' => $this->path,
            'queryString' => $this->queryString,
            'userAgent' => $this->userAgent,
            'referer' => $this->referer,
            'acceptLanguage' => $this->acceptLanguage,
            'ip' => $this->ip,
            'hitAt' => $this->hitAt->format('Y-m-d H:i:s.u'),
            'isNewSession' => $this->isNewSession,
        ];
    }
}
