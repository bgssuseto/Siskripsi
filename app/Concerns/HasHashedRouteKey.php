<?php

namespace App\Concerns;

use Hashids\Hashids;

/**
 * Encodes a model's numeric primary key as an opaque string for use in URLs,
 * so route parameters don't expose sequential, guessable database IDs.
 * The real integer ID is never changed in the database — only the route
 * binding (URL <-> model resolution) and the `hash_id` accessor are affected.
 */
trait HasHashedRouteKey
{
    public function initializeHasHashedRouteKey(): void
    {
        if (!in_array('hash_id', $this->appends ?? [])) {
            $this->appends[] = 'hash_id';
        }
    }

    protected static function hashids(): Hashids
    {
        return new Hashids(config('app.key') . '|' . static::class, 10);
    }

    public static function encodeHashId(int $id): string
    {
        return static::hashids()->encode($id);
    }

    public static function decodeHashId(string $hash): ?int
    {
        $decoded = static::hashids()->decode($hash);

        return $decoded[0] ?? null;
    }

    /**
     * Outbound: route($name, $model) and {{ route(...) }} helpers use this
     * to generate the URL segment, so every existing call site that passes
     * the model itself (rather than ->id) gets a hashed URL automatically.
     */
    public function getRouteKey()
    {
        return $this->getHashIdAttribute();
    }

    /**
     * Inbound: Laravel calls this to resolve the URL segment back to a model.
     * Always decodes and looks up by the real primary key, independent of
     * whatever getRouteKeyName() says.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $id = static::decodeHashId((string) $value);

        if ($id === null) {
            return null;
        }

        return $this->where($this->getKeyName(), $id)->first();
    }

    public function getHashIdAttribute(): string
    {
        return static::encodeHashId((int) $this->getKey());
    }
}
