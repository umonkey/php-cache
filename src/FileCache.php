<?php

/**
 * Simple PSR-16 compliant file system driven cache.
 **/

declare(strict_types=1);

namespace Umonkey\Cache;

use Psr\SimpleCache\CacheInterface;

class FileCache implements CacheInterface
{
    /**
     * Root folder.
     *
     * @var string
     **/
    protected $root;

    /**
     * Default TTL value.
     *
     * @var int|null
     **/
    protected $ttl;

    public function __construct($settings)
    {
        $this->root = $settings['cache']['root_dir'];
        $this->ttl = $settings['cache']['default_ttl'] ?? null;
    }

    public function get($key, $default = null)
    {
        $key = md5($key);
        $path = $this->root . '/' . $key;

        if (!file_exists($path)) {
            return $default;
        }

        if (!is_readable($path)) {
            return $default;
        }

        return unserialize(file_get_contents($path));
    }

    public function set($key, $value, $ttl = null)
    {
        $key = md5($key);
        $path = $this->root . '/' . $key;

        if (!is_writable($this->root)) {
            throw new \RuntimeException('cache folder is not writable');
        }

        file_put_contents($path, serialize($value));
    }

    public function delete($key)
    {
        $key = md5($key);
        $path = $this->root . '/' . $key;

        if (file_exists($path)) {
            if (false === unlink($path)) {
                throw new \RuntimeException('could not delete cache item');
            }
        }
    }

    public function clear()
    {
        foreach (glob($this->root . '/*') as $fn) {
            unlink($fn);
        }
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
        $path = $this->root . '/' . $key;
        return is_readable($path);
    }
}
