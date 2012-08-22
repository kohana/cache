<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * [Kohana Cache](api/Kohana_Cache) XCache driver.
 * Provides XCache variables cache support for the Kohana Cache library.
 * Implements Cache_Arithmetic.
 * Uses the [igbinary](http://pecl.php.net/package/igbinary) extension if available.
 *
 * ### Supported cache engines
 *
 * *  [XCache](http://xcache.lighttpd.net/)
 *
 * ### Configuration example
 *
 * Below is an example of a _xcache_ server configuration.
 *
 *     return array(
 *          'default' => array(                 // default group
 *              'driver'         => 'xcache',   // using XCache driver
 *          ),
 *     )
 *
 * In cases where only one cache group is required, if the group name
 * is the same as [Cache::$default], you don't need to pass it.
 *
 * #### General cache group configuration settings
 *
 * Below are the settings available to all types of cache driver.
 *
 * Name           | Required | Description
 * -------------- | -------- | ---------------------------------------------------------------
 * driver         | __YES__  | (_string_) The driver type to use
 *
 * ### System requirements
 *
 * *  Kohana 3.x
 * *  PHP 5.2.4 or greater
 * *  XCache PHP extension
 *
 * @package    Kohana/Cache
 * @category   Base
 * @version    1.0
 * @author     Johan Lindh <johan@linkdata.se>
 * @copyright  (c) 2012 Johan Lindh
 * @license    http://kohanaphp.com/license
 * @uses       [igbinary](http://pecl.php.net/package/igbinary)
 */
class Kohana_Cache_Xcache extends Cache implements Cache_Arithmetic {

	/**
	 * Recommended cache id separator.
	 *
	 * @var string
	 */
	protected $_id_separator = ':';

	/**
	 * Characters to remove or replace in cache ID's.
	 * Control characters `\x01` through `\x1F` are always removed unless
	 * explicitly replaced. `\x00` is always removed.
	 *
	 * @var string
	 */
	protected $_sanitize_id_from = "\\/ ";

	/**
	 * Characters that replace those that `$_sanitize_id_from` starts with.
	 *
	 * @var string
	 */
	protected $_sanitize_id_to = "___";

	/**
	 * Checks for existence of the PHP XCache extension
	 * and sets the id sanitation data.
	 *
	 * @param   array     configuration
	 * @throws  Cache_Exception
	 */
	protected function __construct(array $config)
	{
		if ( ! extension_loaded('xcache'))
			throw new Cache_Exception('PHP XCache extension is not available.');

		parent::__construct($config);

		// XCache actually allows these in cache id's.
		// We remove them just to prevent injection attacks if the
		// id's are sent as-is to the browser or database.
		$this->_sanitize_id_init(" &<>\"\\", "_-()\'/");
	}

	/**
	 * Set the id sanitization strings used by `_sanitize_id()`.
	 * Control characters not already in `$from` will be appended.
	 * If the [strtr()](http://php.net/manual/en/function.strtr.php)
	 * replacement string `$to` is shorter than `$from`,
	 * it is extended with null characters.
	 *
	 * @param   string	Characters to replace or remove from ids
	 * @param	string	Replacement characters
	 * @return	$this
	 * @throws  Cache_Exception
	 */
	protected function _sanitize_id_init($from = NULL, $to = NULL)
	{
		// If from string not provided, keep the old
		if ($from === NULL)
			$from = $this->_sanitize_id_from;

		// Append control characters not already there
		for ($i = 1; $i < 0x20; $i ++)
			if (strpos($from, chr($i)) === FALSE)
				$from .= chr($i);

		// Make sure the ID_SEPARATOR is not in $from.
		if (strpos($from, $this->_id_separator) !== FALSE)
			throw new Cache_Exception('Don\'t sanitize $_id_separator');

		// If to string not provided, keep the exisiting one
		if ($to === NULL)
			$to = $this->_sanitize_id_from;

		// Pad $to with null characters as needed
		$to .= str_repeat(chr(0), max(strlen($from) - strlen($to), 0));

		$this->_sanitize_id_from = $from;
		$this->_sanitize_id_to = $to;
		return $this;
	}

	/**
	 * Replace or remove characters not allowed in cache id's and
	 * append `$_id_separator`. Appending the separator allows the
	 * cache to safely store metadata after the `$id` as long as the
	 * metadata itself does not contain `$_id_separator`.
	 *
	 *     // Sanitize a cache id
	 *     $id = $this->_sanitize_id($id);
	 *
	 * @param   string   cache id to sanitize
	 * @return  string
	 */
	protected function _sanitize_id($id)
	{
		return str_replace(chr(0), '',
			strtr($id, $this->_sanitize_id_from, $this->_sanitize_id_to)
			) . $this->_id_separator;
	}

	/**
	 * Retrieve the value of `$id` from the cache.
	 *
	 * @param   string   id of cache entry
	 * @param   mixed    default value to return if id is not found
	 * @return  mixed
	 */
	public function get($id, $default = NULL)
	{
		$id = $this->_sanitize_id($id);
		if (xcache_isset($id))
			return xcache_get($id);
		if (function_exists('igbinary_unserialize') and xcache_isset($id . 'ig'))
			return igbinary_unserialize(xcache_get($id . 'ig'));
		if (xcache_isset($id . 'php'))
			return unserialize(xcache_get($id . 'php'));
		return $default;
	}

	/**
	 * Set the value of `$id` in the cache.
	 *
	 * @param   string   id of cache entry
	 * @param   mixed    data to set to cache
	 * @param   integer  lifetime in seconds
	 * @return  boolean
	 */
	public function set($id, $data, $lifetime = Cache::DEFAULT_EXPIRE)
	{
		$id = $this->_sanitize_id($id);
		if (is_object($data) or is_callable($data) or is_resource($data))
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
	 * @param   string   id of cache entry to delete
	 * @return  boolean
	 */
	public function delete($id)
	{
		return xcache_unset_by_prefix($this->_sanitize_id($id));
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
