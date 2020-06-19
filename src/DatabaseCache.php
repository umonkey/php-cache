<?php

/**
 * Simple PSR-16 compliant database driven cache.
 **/

declare(strict_types=1);

namespace Umonkey\Cache;

use Psr\SimpleCache\CacheInterface;
use Umonkey\Database;

class DatabaseCache implements CacheInterface
{
    /**
     * Database interface.
     *
     * @var Database
     **/
    protected $db;

    /**
     * Default TTL value.
     *
     * @var int|null
     **/
    protected $ttl;

    public function __construct(Database $db, $settings)
    {
        $this->db = $db;
        $this->ttl = $settings['cache']['default_ttl'] ?? null;
    }

    public function get($key, $default = null)
    {
        $key = md5($key);

        $res = $this->db->fetchOne('SELECT * FROM `cache` WHERE `key` = ?', [$key]);
        if (null === $res) {
            return $default;
        }

        if ($res['expires'] !== null && time() > $res['expires']) {
            return $default;
        }

        return unserialize($res['value']);
    }

    public function set($key, $value, $ttl = null)
    {
        $key = md5($key);

        $ttl = $ttl ?? $this->ttl;

        $old = $this->db->fetchCell('SELECT `key` FROM `cache` WHERE `key` = ?', [$key]);
        if ($old !== null) {
            $this->db->update('cache', [
                'value' => serialize($value),
                'expires' => $ttl === null ? null : time() + $ttl,
            ], [
                'key' => $key,
            ]);
        } else {
            $this->db->insert('cache', [
                'key' => $key,
                'value' => serialize($value),
                'expires' => $ttl === null ? null : time() + $ttl,
            ]);
        }
    }

    public function delete($key)
    {
        $key = md5($key);
        $this->db->query('DELETE FROM `cache` WHERE `key` = ?', [$key]);
    }

    public function clear()
    {
        $this->db->query('DELETE FROM `cache`');
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v, $ttl);
        }
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
    }

    public function has($key)
    {
        $key = md5($key);

        $res = $this->db->fetchOne('SELECT * FROM `cache` WHERE `key` = ?', [$key]);
        return $res !== null;
    }
}
