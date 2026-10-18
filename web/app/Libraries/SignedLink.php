<?php

namespace App\Libraries;

/**
 * Short-lived signed query strings, so a signed-in mobile user (bearer
 * token, no web session) can open a protected web page such as the
 * flipbook reader. The signature binds the user, the resource and an
 * expiry time.
 */
class SignedLink
{
    public static function sign(string $resource, int $userId, int $ttlSeconds = 7200): string
    {
        $exp = time() + $ttlSeconds;

        return http_build_query(['u' => $userId, 'e' => $exp, 's' => self::mac($resource, $userId, $exp)]);
    }

    /**
     * The user id the signature was issued for, or null if it is missing,
     * tampered with or expired.
     */
    public static function verify(string $resource, array $query): ?int
    {
        $u = (int) ($query['u'] ?? 0);
        $e = (int) ($query['e'] ?? 0);
        $s = (string) ($query['s'] ?? '');
        if ($u <= 0 || $e < time() || $s === '') {
            return null;
        }

        return hash_equals(self::mac($resource, $u, $e), $s) ? $u : null;
    }

    private static function mac(string $resource, int $userId, int $exp): string
    {
        $key = (string) (config('Encryption')->key ?: env('encryption.key', '') ?: config('App')->baseURL);

        return hash_hmac('sha256', $resource . '|' . $userId . '|' . $exp, $key);
    }
}
