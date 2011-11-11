<?php defined('SYSPATH') or die('No direct script access.');
/**
 * @package    Kohana/Cache
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Cache_Memcached extends Cache implements Cache_Arithmetic {

	// memcached has a maximum cache lifetime of 30 days
	const CACHE_CEILING = 2592000;

	/**
	 * memcached resource
	 *
	 * @var memcached
	 */
	protected $_memcached;

	/**
	 * The default configuration for the memcached server
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 * Constructs the memcached Kohana_Cache object
	 *
	 * @param   array     configuration
	 * @throws  Cache_Exception
	 */
	public function __construct(array $config)
	{
		// Check for the memcached extention
		if ( ! extension_loaded('memcached'))
		{
			throw new Cache_Exception('memcached PHP extention not loaded');
		}

		parent::__construct($config);

        // use persistent?
        if (FALSE === $pool = Arr::get($this->_config, 'persistent', FALSE)) {
            $this->_memcached = new Memcached();
        } else {
            $this->_memcached = new Memcached($pool);
        }

        // Load options
        $this->_options = Arr::get($this->_config, 'options', array());
        $this->_memcached->setOptions($this->_options);

        // Load servers from configuration
   		$servers = Arr::get($this->_config, 'servers', NULL);

		if ( ! $servers)
		{
			// Throw an exception if no server found
			throw new Cache_Exception('No memcached servers defined in configuration');
		}

		// Add the memcached servers to the pool
        $this->_memcached->addServers($servers);
	}

	/**
	 * Retrieve a cached value entry by id.
	 * 
	 *     // Retrieve cache entry from memcached group
	 *     $data = Cache::instance('memcached')->get('foo');
	 * 
	 *     // Retrieve cache entry from memcached group and return 'bar' if miss
	 *     $data = Cache::instance('memcached')->get('foo', 'bar');
	 *
	 * @param   string   id of cache to entry
	 * @param   string   default value to return if cache miss
	 * @return  mixed
	 * @throws  Cache_Exception
	 */
	public function get($id, $default = NULL)
	{
		// Get the value from memcached
		$value = $this->_memcached->get($this->_sanitize_id($id));

		// If the value wasn't found, normalise it
		if ($value === FALSE)
		{
			$value = (NULL === $default) ? NULL : $default;
		}

		// Return the value
		return $value;
	}

	/**
	 * Set a value to cache with id and lifetime
	 * 
	 *     $data = 'bar';
	 * 
	 *     // Set 'bar' to 'foo' in memcached group for 10 minutes
	 *     if (Cache::instance('memcached')->set('foo', $data, 600))
	 *     {
	 *          // Cache was set successfully
	 *          return
	 *     }
	 *
	 * @param   string   id of cache entry
	 * @param   mixed    data to set to cache
	 * @param   integer  lifetime in seconds, maximum value 2592000
	 * @return  boolean
	 */
	public function set($id, $data, $lifetime = 3600)
	{
		// If the lifetime is greater than the ceiling
		if ($lifetime > Cache_Memcached::CACHE_CEILING)
		{
			// Set the lifetime to maximum cache time
			$lifetime = Cache_Memcached::CACHE_CEILING + time();
		}
		// Else if the lifetime is greater than zero
		elseif ($lifetime > 0)
		{
			$lifetime += time();
		}
		// Else
		else
		{
			// Normalise the lifetime
			$lifetime = 0;
		}

		// Set the data to memcached
		return $this->_memcached->set($this->_sanitize_id($id), $data, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 * 
	 *     // Delete the 'foo' cache entry immediately
	 *     Cache::instance('memcached')->delete('foo');
	 * 
	 *     // Delete the 'bar' cache entry after 30 seconds
	 *     Cache::instance('memcached')->delete('bar', 30);
	 *
	 * @param   string   id of entry to delete
	 * @param   integer  timeout of entry, if zero item is deleted immediately, otherwise the item will delete after the specified value in seconds
	 * @return  boolean
	 */
	public function delete($id, $timeout = 0)
	{
		// Delete the id
		return $this->_memcached->delete($this->_sanitize_id($id), $timeout);
	}

	/**
	 * Delete all cache entries.
	 * 
	 * Beware of using this method when
	 * using shared memory cache systems, as it will wipe every
	 * entry within the system for all clients.
	 * 
	 *     // Delete all cache entries in the default group
	 *     Cache::instance('memcached')->delete_all();
	 *
	 * @return  boolean
	 */
	public function delete_all()
	{
		return $this->_memcached->flush();
	}

	/**
	 * Increments a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string    id of cache entry to increment
	 * @param   int       step value to increment by
	 * @return  integer
	 * @return  boolean
	 */
	public function increment($id, $step = 1)
	{
		return $this->_memcached->increment($id, $step);
	}

	/**
	 * Decrements a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string    id of cache entry to decrement
	 * @param   int       step value to decrement by
	 * @return  integer
	 * @return  boolean
	 */
	public function decrement($id, $step = 1)
	{
		return $this->_memcached->decrement($id, $step);
	}
}