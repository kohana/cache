<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache Memcache Driver
 * 
 * @package Cache
 * @author Sam de Freyssinet <sam@def.reyssi.net>
 * @copyright (c) 2009 Sam de Freyssinet
 * @license ISC http://www.opensource.org/licenses/isc-license.txt
 * Permission to use, copy, modify, and/or distribute 
 * this software for any purpose with or without fee
 * is hereby granted, provided that the above copyright 
 * notice and this permission notice appear in all copies.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS 
 * ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO 
 * EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, 
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES 
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, 
 * WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER 
 * TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH 
 * THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */
class Cache_Memcache extends Cache {

	/**
	 * This libraries instance
	 *
	 * @var Cache_Memcache
	 */
	static protected $_instance;

	/**
	 * Create a new instance
	 *
	 * @return Cache_Memcache
	 * @access public
	 * @static
	 */
	static public function instance()
	{
		if (NULL === Cache_Memcache::$_instance)
			Cache_Memcache::$_instance = new Cache_Memcache;

		return Cache_Memcache::$_instance;
	}

	/**
	 * Memcache resource
	 *
	 * @var Memcache
	 */
	protected $_memcache;

	/**
	 * Flags to use when storing values
	 *
	 * @var string
	 */
	protected $_flags;

	/**
	 * Constructs the memcache object
	 *
	 * @access protected
	 * @throws Cache_Exception
	 */
	protected function __construct()
	{
		// Check for the memcache extention
		if ( ! extension_loaded('memcache'))
			throw new Cache_Exception('Memcache PHP extention not loaded');

		// Load the configuration
		$config = Kohana::config('cache-memcache');

		// Setup Memcache
		$this->_memcache = new Memcache;

		$servers = $config->get('servers');

		// Add the memcache servers to the pool
		foreach ($servers as $server)
		{
			if ( ! $this->_memcache->addServer($server['host'], $server['port'], $server['persistent']))
				throw new Cache_Exception('Memcache could not connect to host \':host\' using port \':port\'', array(':host' => $server['host'], ':port' => $server['port']));
		}

		// Setup the flags
		$this->_flags = $config->get('compression') ? MEMCACHE_COMPRESSED : FALSE;
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
	public function get($id, $default = NULL)
	{
		// Get the value from Memcache
		$value = $this->_memcache->get($this->sanitize_id($id));

		// If the value wasn't found, normalise it
		if (FALSE === $value)
			$value = (NULL === $default) ? NULL : $default;

		// Return the value
		return $value;
	}

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
	 * @throws Cache_Exception
	 */
	public function set($id, $data, $lifetime = NULL)
	{
		// Normalise the lifetime
		if (NULL === $lifetime)
			$lifetime = 0;
		else
			$lifetime += time();

		// Set the data to memcache
		return $this->_memcache->set($this->sanitize_id($id), $data, $this->_flags, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param string $id 
	 * @return boolean
	 * @access public
	 */
	public function delete($id, $timeout = 0)
	{
		// Delete the id
		return $this->_memcache->delete($this->sanitize_id($id), $timeout);
	}

	/**
	 * Delete all cache entries
	 *
	 * @return boolean
	 * @access public
	 */
	public function delete_all()
	{
		$result = $this->_memcache->flush();

		// We must sleep after flushing, or overwriting will not work!
		// @see http://php.net/manual/en/function.memcache-flush.php#81420
		sleep(1);

		return $result;
	}
}