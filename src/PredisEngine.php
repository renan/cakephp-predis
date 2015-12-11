<?php
namespace Renan\Cake\Predis;

use Cake\Cache\CacheEngine;
use Predis\Client;
use Predis\ClientInterface;

final class PredisEngine extends CacheEngine
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * The default config used unless overridden by runtime configuration
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     * - `connections` One or more URI strings or named arrays, eg:
     *    URI: 'tcp://127.0.0.1:6379'
     *    Named array: ['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => 6379]
     * - `options` A list of options as accepted by Predis\Client
     *
     * @var array
     */
    protected $_defaultConfig = [
        'duration' => 3600,
        'groups' => [],
        'prefix' => 'cake_',
        'probability' => 100,
        'connections' => 'tcp://127.0.0.1:6379',
        'options' => null,
    ];

    /**
     * Initialize the cache engine
     *
     * Called automatically by the cache frontend. Merge the runtime config with the defaults
     * before use.
     *
     * @param array $config Associative array of parameters for the engine
     * @return bool True if the engine has been successfully initialized, false if not
     */
    public function init(array $config = [])
    {
        parent::init($config);

        $this->client = new Client($this->_config['connections'], $this->_config['options']);
        $this->client->connect();

        return $this->client->isConnected();
    }

    /**
     * Write value for a key into cache
     *
     * @param string $key Identifier for the data
     * @param mixed $value Data to be cached
     * @return bool True if the data was successfully cached, false on failure
     */
    public function write($key, $value)
    {
        $key = $this->_key($key);
        $value = is_int($value)
            ? (int) $value
            : serialize($value);

        if ($this->_config['duration'] === 0) {
            return (string) $this->client->set($key, $value) === 'OK';
        }

        return (string) $this->client->setex($key, $this->_config['duration'], $value) === 'OK';
    }

    /**
     * Read a key from the cache
     *
     * @param string $key Identifier for the data
     * @return mixed The cached data, or false if the data doesn't exist, has expired, or if there was an error fetching it
     */
    public function read($key)
    {
        $key = $this->_key($key);
        $value = $this->client->get($key);

        if ($value === null) {
            return false;
        }

        return ctype_digit($value)
            ? (int) $value
            : unserialize($value);
    }

    /**
     * Increment a number under the key and return incremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to add
     * @return bool|int New incremented value, false otherwise
     */
    public function increment($key, $offset = 1)
    {
        $key = $this->_key($key);
        if (! $this->client->exists($key)) {
            return false;
        }

        return $this->client->incrby($key, $offset);
    }

    /**
     * Decrement a number under the key and return decremented value
     *
     * @param string $key Identifier for the data
     * @param int $offset How much to subtract
     * @return bool|int New incremented value, false otherwise
     */
    public function decrement($key, $offset = 1)
    {
        $key = $this->_key($key);
        if (! $this->client->exists($key)) {
            return false;
        }

        return $this->client->decrby($key, $offset);
    }

    /**
     * Delete a key from the cache
     *
     * @param string $key Identifier for the data
     * @return bool True if the value was successfully deleted, false if it didn't exist or couldn't be removed
     */
    public function delete($key)
    {
        $key = $this->_key($key);
        if (! $this->client->exists($key)) {
            return false;
        }

        return $this->client->del($key) === 1;
    }

    /**
     * Delete all keys from the cache
     *
     * @param bool $check if true will check expiration, otherwise delete all
     * @return bool True if the cache was successfully cleared, false otherwise
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }

        $keys = $this->client->keys($this->_config['prefix'] . '*');
        foreach ($keys as $key) {
            $this->client->del($key);
        }

        return true;
    }
}
