<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Kohana Cache XCache Driver
 *
 * Requires Xcache
 * http://xcache.lighttpd.net/
 *
 * Makes use of igbinary if installed
 * http://pecl.php.net/package/igbinary
 *
 * @package    XCache
 * @category   Cache
 * @author     Johan Lindh <johan@linkdata.se>
 * @copyright  (c) 2012 Johan Lindh
 * @license    http://kohanaphp.com/license
 */
class Cache_Xcache extends Cache implements Cache_Arithmetic {

	/**
	 * Check for existence of the xcache extension
	 *
	 * @param   array     configuration
	 * @throws  Cache_Exception
	 */
	protected function __construct(array $config)
	{
		if ( ! extension_loaded('xcache'))
			throw new Cache_Exception('PHP XCache extension is not available.');
		parent::__construct($config);
	}

	protected function _sanitize_id($id)
	{
		return strtr($id, " \\\"\'/\x00", '______') . '/';
	}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param   string   id of cache to entry
	 * @param   mixed    default value to return if cache miss
	 * @return  mixed
	 */
	public function get($id, $default = NULL)
	{
		$id = $this->_sanitize_id($id);
		if (xcache_isset($id))
			return xcache_get($id);
		if (function_exists('igbinary_unserialize') AND xcache_isset($id . 'ig'))
			return igbinary_unserialize(xcache_get($id . 'ig'));
		if (xcache_isset($id . 'php'))
			return unserialize(xcache_get($id . 'php'));
		return $default;
	}

	/**
	 * Set a value based on an id. Optionally add tags.
	 *
	 * @param   string   id of cache entry
	 * @param   mixed    data to set to cache
	 * @param   integer  lifetime in seconds
	 * @return  boolean
	 */
	public function set($id, $data, $lifetime = 3600)
	{
		$id = $this->_sanitize_id($id);
		if (is_object($data) OR is_callable($data) OR is_resource($data))
		{
			if (function_exists('igbinary_serialize'))
				return xcache_set($id . 'ig', igbinary_serialize($data), $lifetime);
			return xcache_set($id . 'php', serialize($data), $lifetime);
		}
		return xcache_set($id, $data, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param   string   id of entry to delete
	 * @return  boolean
	 */
	public function delete($id)
	{
		$id = $this->_sanitize_id($id);
		return xcache_unset_by_prefix($id);
	}

	/**
	 * Delete all cache entries
	 * To use this method xcache.admin.enable_auth has to be Off in xcache.ini
	 *
	 * @return  void
	 */
	public function delete_all()
	{
		xcache_clear_cache(XC_TYPE_VAR, 0);
	}

	/**
	 * Increments a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string    id of cache entry to increment
	 * @param   integer   step value to increment by
	 * @return  integer
	 */
	public function increment($id, $step = 1)
	{
		return xcache_inc($this->_sanitize_id($id), $step);
	}

	/**
	 * Decrements a given value by the step value supplied.
	 * Useful for shared counters and other persistent integer based
	 * tracking.
	 *
	 * @param   string    id of cache entry to decrement
	 * @param   integer   step value to decrement by
	 * @return  integer
	 */
	public function decrement($id, $step = 1)
	{
		return xcache_dec($this->_sanitize_id($id), $step);
	}

}
