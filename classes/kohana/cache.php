<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache
 * 
 * Caching library for Kohana PHP 3
 *
 * @package    Kohana
 * @category   Cache
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
abstract class Kohana_Cache {

	public static $instances = array();

	/**
	 * Get a singleton cache instance. If no configuration is specified,
	 * it will be loaded using the standard configuration 'type' setting.
	 *
	 * @param   string   the name of the cache driver to use [Optional]
	 * @return  Kohana_Cache
	 */
	public static function instance($type = NULL)
	{
		// Resolve type
		$type === NULL and $type = Kohana::config('cache.type');

		// Return the current type if initiated already
		if (isset(Cache::$instances[$type]))
			return Cache::$instances[$type];

		// Create a new cache type instance
		$cache_class = 'Cache_'.ucfirst($type);
		Cache::$instances[$type] = new $cache_class;

		// Return the instance
		return Cache::$instances[$type];
	}

	/**
	 * The default expiry for cache items
	 *
	 * @var  int
	 */
	protected $_default_expire;

	/**
	 * Ensures singleton pattern is observed, loads the default expiry
	 */
	protected function __construct()
	{
		$this->_default_expire = Kohana::config('cache.default-expire');
	}

	/**
	 * Overload the __clone() method to prevent cloning
	 *
	 * @return void
	 * @access public
	 */
	public function __clone()
	{
		throw new Kohana_Cache_Exception('Cloning of Kohana_Cache objects is forbidden');
	}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param string $id 
	 * @param string $default [Optional] Default value to return if id not found
	 * @return mixed
	 * @access public
	 * @abstract
	 */
	abstract public function get($id, $default = NULL);

	/**
	 * Set a value based on an id. Optionally add tags.
	 * 
	 * Note : Some caching engines do not support
	 * tagging
	 *
	 * @param string $id 
	 * @param string $data 
	 * @param integer $lifetime [Optional]
	 * @return boolean
	 * @access public
	 * @abstract
	 */
	abstract public function set($id, $data, $lifetime = NULL);

	/**
	 * Delete a cache entry based on id
	 *
	 * @param string $id 
	 * @param integer $timeout [Optional]
	 * @return boolean
	 * @access public
	 * @abstract
	 */
	abstract public function delete($id);

	/**
	 * Delete all cache entries
	 *
	 * @return boolean
	 * @access public
	 * @abstract
	 */
	abstract public function delete_all();

	/**
	 * Replaces troublesome characters with underscores.
	 *
	 * @param string $id
	 * @return string
	 * @access protected
	 */
	protected function sanitize_id($id)
	{
		// Change slashes and spaces to underscores
		return str_replace(array('/', '\\', ' '), '_', $id);
	}
}
// End Kohana_Cache