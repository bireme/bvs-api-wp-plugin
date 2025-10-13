<?php
namespace BV\Support;

if (!defined('ABSPATH')) exit;

/**
 * Cache simples via transients.
 * TTL ajustável via filtro 'bv_cache_ttl' (padrão 3600s).
 */

//Eu não sei se isso é realmente bom (vamos testar para ver se ajuda na performance, não fica chamando api toda hora)
final class Cache {
    public static function remember(string $key, callable $callback, ?int $ttl = null) {
        $hit = get_transient($key);
        if ($hit !== false) return $hit;

        $value = $callback();
        $ttl = $ttl ?? (int) apply_filters('bv_cache_ttl', 3600);
        set_transient($key, $value, $ttl);
        return $value;
    }

    public static function forget(string $key): void {
        delete_transient($key);
    }
}
