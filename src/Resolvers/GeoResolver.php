<?php

declare(strict_types=1);

namespace LaravelGlimpse\Resolvers;

use GeoIp2\Database\Reader;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use LaravelGlimpse\Contracts\Resolver;
use RuntimeException;
use SxGeo;
use Throwable;

use function dirname;

final class GeoResolver implements Resolver
{
    use Macroable;

    public function __construct(
        #[Config('glimpse.geo.driver', 'null')]
        private readonly string $driver
    ) {}

    public function resolve(Request $request): array
    {
        $ip = $request->ip();

        if (! $ip || $this->driver === 'null') {
            return $this->empty();
        }

        $method = 'resolve'.Str::ucfirst(Str::camel($this->driver));
        if (method_exists($this, $method) || self::hasMacro($method)) {
            return $this->{$method}($ip);
        }

        return $this->empty();
    }

    /**
     * @return array{country_code: ?string, region: ?string, city: ?string}
     */
    private function empty(): array
    {
        return [
            'country_code' => null,
            'region' => null,
            'city' => null,
        ];
    }

    /**
     * @return array{country_code: ?string, region: ?string, city: ?string}
     */
    private function resolveSxgeo(string $ip): array
    {
        $database = config('glimpse.geo.sxgeo_db');
        if (! $database || ! File::exists($database)) {
            Log::warning('SxGeo database not found at '.$database);

            return $this->empty();
        }

        try {
            // SxGeo ships as a standalone PHP class — include if available.
            // Developers must place SxGeo.php alongside the .dat file.
            $sxGeoClass = dirname((string) $database).'/SxGeo.php';

            if (! class_exists('SxGeo') && File::exists($sxGeoClass)) {
                require_once $sxGeoClass;
            }

            throw_unless(class_exists('SxGeo'), RuntimeException::class, 'SxGeo class not found');

            $sxGeo = new SxGeo($database);
            $data = $sxGeo->getCityFull($ip);

            if (empty($data)) {
                return $this->empty();
            }

            return [
                'country_code' => $data['country']['iso'] ?? null,
                'country_name' => $data['country']['name_en'] ?? null,
                'region' => $data['region']['name_en'] ?? null,
                'city' => $data['city']['name_en'] ?? null,
            ];
        } catch (Throwable) {
            // Private/reserved IPs, unmapped addresses, etc. are silently ignored.
            return $this->empty();
        }
    }

    /**
     * @return array{country_code: ?string, region: ?string, city: ?string}
     */
    private function resolveMaxmind(string $ip): array
    {
        $database = config('glimpse.geo.maxmind_db');
        if (! $database || ! File::exists($database)) {
            Log::warning('SxGeo database not found at '.$database);

            return $this->empty();
        }

        try {
            $reader = new Reader($database, [app()->currentLocale(), app()->getFallbackLocale(), 'en']);
            $record = $reader->city($ip);

            return [
                'country_code' => $record->country->isoCode,
                'region' => $record->mostSpecificSubdivision->name,
                'city' => $record->city->name,
            ];
        } catch (Throwable) {
            // Private/reserved IPs, unmapped addresses, etc. are silently ignored.
            return $this->empty();
        }
    }
}
