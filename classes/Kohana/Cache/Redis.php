<?php defined('SYSPATH') or die('No direct script access.');

/**
 * A Redis driver for the Kohana cache module.
 */
class Kohana_Cache_Redis extends Cache implements Cache_Arithmetic {

	/**
	 * A Redis instance.
	 *
	 * @var Redis A Redis instance.
	 */
	protected $_redis;

	/**
	 * Checks for the existence of the PhpRedis extension and instantiates a connection from the settings.
	 *
	 * @param  array  $config  A configuration array.
	 * @throws Cache_Exception
	 */
	protected function __construct(array $config)
	{
		// Check that the PhpRedis extension is loaded.
		if ( ! extension_loaded('redis'))
		{
			throw new Cache_Exception('The PhpRedis extension is not available.');
		}

		// Define a default settings array.
		$default_settings = array(
			'host' => 'localhost',
			'port' => 6379
		);

		// Merge the default settings with the user-defined settings.
		$settings = Arr::merge($default_settings, (array)Kohana::$config->load('cache.redis'));

		// Create a new Redis instance and start a connection using the settings provided.
		$this->_redis = new Redis;
		$this->_redis->connect($settings['host'], $settings['port']);

		// Call construct on the parent.
		parent::__construct($config);
	}

	/**
	 * Retrieve a cached value entry by ID.
	 *
	 *     // Retrieve a cached entry from the redis group.
	 *     $data = Cache::instance('redis')->get('foo');
	 *
	 *     // Retrieve a cached entry from redis group and return 'bar' if it fails.
	 *     $data = Cache::instance('redis')->get('foo', 'bar');
	 *
	 * @param   string  $id       ID of the cache entry to fetch.
	 * @param   string  $default  The default value to return if retrieval fails.
	 * @return  mixed
	 * @throws  Cache_Exception
	 */
	public function get($id, $default = NULL)
	{
		// Fetch the entry by key.
		$data = $this->_redis->get($this->_sanitize_id($id));

		// Return the entry, or a default value if it fails.
		return $data ? $data : $default;
	}

	/**
	 * Set a value in the cache with an ID and lifetime value.
	 *
	 *     $data = 'bar';
	 *
	 *     // Set 'bar' to 'foo' in the redis group, using the default expiry length.
	 *     Cache::instance('redis')->set('foo', $data);
	 *
	 *     // Set 'bar' to 'foo' in redis group for 30 seconds.
	 *     Cache::instance('redis')->set('foo', $data, 30);
	 *
	 * @param   string   $id        ID of the cache entry to set.
	 * @param   string   $data      The data to set.
	 * @param   integer  $lifetime  The lifetime of the entry.
	 * @return  boolean
	 */
	public function set($id, $data, $lifetime = NULL)
	{
		// If no lifetime is defined, use the default lifetime.
		if ($lifetime === NULL)
		{
			$lifetime = Arr::get($this->_config, 'default_expire', Cache::DEFAULT_EXPIRE);
		}

		// Return the result (will always be TRUE).
		return $this->_redis->set($this->_sanitize_id($id), $data, $lifetime);
	}

	/**
	 * Delete a cache entry based on ID.
	 *
	 *     // Delete the 'foo' entry from the redis group.
	 *     Cache::instance('redis')->delete('foo');
	 *
	 * @param   string  $id  ID to remove from cache.
	 * @return  int
	 */
	public function delete($id)
	{
		// Return the result (the number of keys deleted).
		return $this->_redis->del($this->_sanitize_id($id));
	}

	/**
	 * Delete all cache entries.
	 *
	 * Beware of using this method when using shared memory cache systems, as it will wipe every entry within the
	 * system for all clients.
	 *
	 *     // Delete all cache entries in the redis group.
	 *     Cache::instance('redis')->delete_all();
	 *
	 * @return  boolean
	 */
	public function delete_all()
	{
		// Return the result (will always be TRUE).
		return $this->_redis->flushAll();
	}

	/**
	 * Increments a given value by the step value supplied. Useful for shared counters and other persistent integer
	 * based tracking.
	 *
	 * @param   string    $id    ID of the cache entry to increment.
	 * @param   int       $step  The value to increment by.
	 * @return  int
	 */
	public function increment($id, $step = 1)
	{
		// Return the result (the new value).
		return $this->_redis->incrBy($id, $step);
	}

	/**
	 * Decrements a given value by the step value supplied. Useful for shared counters and other persistent integer
	 * based tracking.
	 *
	 * @param   string    $id    ID of the cache entry to decrement.
	 * @param   int       $step  The value to decrement by.
	 * @return  int
	 */
	public function decrement($id, $step = 1)
	{
		// Return the result (the new value).
		return $this->_redis->decrBy($id, $step);
	}

}