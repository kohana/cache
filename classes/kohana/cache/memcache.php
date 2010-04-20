<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache Memcache Driver
 * 
 * @package    Kohana
 * @category   Cache
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Cache_Memcache extends Cache {

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
	 * @throws  Kohana_Cache_Exception
	 */
	protected function __construct($config)
	{
		// Check for the memcache extention
		if ( ! extension_loaded('memcache'))
		{
			throw new Kohana_Cache_Exception('Memcache PHP extention not loaded');
		}

		parent::__construct($config);

		// Setup Memcache
		$this->_memcache = new Memcache;

		// Load servers from configuration
		$servers = Arr::get($this->_config, 'servers', NULL);

		if ( ! $servers)
		{
			// Throw an exception if no server found
			throw new Kohana_Cache_Exception('No Memcache servers defined in configuration');
		}

		// Add the memcache servers to the pool
		foreach ($servers as $server)
		{
			if ( ! $this->_memcache->addServer($server['host'], $server['port'], $server['persistent']))
			{
				throw new Kohana_Cache_Exception('Memcache could not connect to host \':host\' using port \':port\'', array(':host' => $server['host'], ':port' => $server['port']));
			}
		}

		// Setup the flags
		$this->_flags = Arr::get($this->_config, 'compression', FALSE) ? MEMCACHE_COMPRESSED : FALSE;
	}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param   string   id 
	 * @param   mixed    default [Optional] Default value to return if id not found
	 * @return  mixed
	 */
	public function get($id, $default = NULL)
	{
		// Get the value from Memcache
		$value = $this->_memcache->get($this->sanitize_id($id));

		// If the value wasn't found, normalise it
		if ($value === FALSE)
		{
			$value = (NULL === $default) ? NULL : $default;
		}

		// Return the value
		return $value;
	}

	/**
	 * Set a value based on an id.
	 *
	 * @param   string   id 
	 * @param   mixed    data 
	 * @param   integer  lifetime [Optional]
	 * @return  boolean
	 */
	public function set($id, $data, $lifetime = NULL)
	{
		if ($lifetime === NULL)
		{
			// Normalise the lifetime
			$lifetime = 0;
		}
		else
		{
			$lifetime += time();
		}

		// Set the data to memcache
		return $this->_memcache->set($this->sanitize_id($id), $data, $this->_flags, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param   string   id 
	 * @return  boolean
	 */
	public function delete($id, $timeout = 0)
	{
		// Delete the id
		return $this->_memcache->delete($this->sanitize_id($id), $timeout);
	}

	/**
	 * Delete all cache entries
	 *
	 * @return  boolean
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