# Simple PSR-16 compatible cache

Mostly for use with my other components in Slim based applications.


## Usage

Initialize by adding this to `config/dependencies.php`:

```
$container['cache'] = function ($c) {
    return $c['callableResolver']->getClassInstance('Umonkey\\Cache\\DatabaseCache');
};
```

Work with the cache like this:

```
use Psr\SimpleCache\CacheInterface;

class SomeClass
{
    /**
     * @var CacheInterface
     **/
    protected $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getValue(string $key): string
    {
        return $this->cache->get($key);
    }

    public function setValue(string $key, $value): void
    {
        $this->cache->set($key, $value);
    }
}
```


## Configuration

Runtime configuration not needed.

Data is stored in the `cache` table, created like this:

```
CREATE TABLE IF NOT EXISTS `cache` (
    `key` CHAR(32) NOT NULL PRIMARY KEY,
    `expires` INTEGER UNSIGNED NULL,
    `value` MEDIUMBLOB,
    KEY(`expires`)
) DEFAULT CHARSET utf8;
```
