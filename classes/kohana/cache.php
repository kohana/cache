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

	// Default expirary for groups without setting
	const DEFAULT_EXPIRE = 3600;

	public static $instances = array();

	/**
	 * Get a singleton cache instance. If no configuration is specified,
	 * it will be loaded using the standard configuration 'type' setting.
	 *
	 * @param   string   the name of the cache driver to use [Optional]
	 * @return  Kohana_Cache
	 */
	public static function instance($group = NULL)
	{
		$config = Kohana::config('cache');

		if ($group === NULL)
		{
			// If there is no config, try and load the default definition
			$group = 'default';
		}

		if ( ! $config->offsetExists($group))
		{
			throw new Kohana_Cache_Exception('Failed to load Kohana Cache group: :group', array(':group' => $group));
		}

		if (isset(Cache::$instances[$group]))
		{
			// Return the current type if initiated already
			return Cache::$instances[$group];
		}

		$config = $config->get($group);

		// Create a new cache type instance
		$cache_class = 'Cache_'.ucfirst($config['driver']);
		Cache::$instances[$group] = new $cache_class($config);

		// Return the instance
		return Cache::$instances[$group];
	}

	/**
	 * Configuration for this object
	 *
	 * @var  Kohana_Config
	 */
	protected $_config;

	/**
	 * Ensures singleton pattern is observed, loads the default expiry
	 * 
	 * @param  Kohana_Config configuration
	 */
	protected function __construct($config)
	{
		$this->_config = $config;
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