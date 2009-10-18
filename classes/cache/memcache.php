<?php defined('SYSPATH') or die('No direct script access.');

class Cache_Memcache extends Cache {

	/**
	 * The key used for the tagging index
	 */
	const CACHE_TAG_KEY = 'ko3_cache_tag_index';

	/**
	 * This libraries instance
	 *
	 * @var Cache_Memcache
	 */
	static protected $_instance;

	/**
	 * Tagging on/off
	 *
	 * @var boolean
	 */
	static protected $_tagging;

	/**
	 * The tagging cache
	 *
	 * @var array
	 */
	static protected $_tag_cache;

	/**
	 * Tag modification status
	 *
	 * @var boolean
	 */
	static protected $_tags_changed = FALSE;

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
		$config = Kohana::config('memcache');

		// Setup Memcache
		$this->_memcache = new Memcache;

		$servers = $config->get('servers');

		// Set the tagging engine status
		Cache_Memcache::$_tagging = $config->get('tagging');

		// Add the memcache servers to the pool
		foreach ($servers as $server)
		{
			if ( ! $this->_memcache->addServer($server['host'], $server['port'], $server['persistent']))
				throw new Cache_Exception('Memcache could not connect to host \':host\' using port \':port\'', array(':host' => $server['host'], ':port' => $server['port']));
		}

		// If tagging is enabled
		if (Cache_Memcache::$_tagging)
		{
			// Load the tag cache
			Cache_Memcache::$_tag_cache = $this->_memcache->get(Cache_Memcache::CACHE_TAG_KEY);

			// If there is no cache stored, create a new empty one
			if ( ! is_array(Cache_Memcache::$_tag_cache))
				Cache_Memcache::$_tag_cache = array();

			// Update the tags change state
			Cache_Memcache::$_tags_changed = TRUE;
		}

		// Setup the flags
		$this->_flags = $config->get('compression') ? MEMCACHE_COMPRESSED : FALSE;

	}

	/**
	 * Handles writing the tags back to Memcache
	 *
	 * @access public
	 */
	public function __destruct()
	{
		if (Cache_Memcache::$_tagging and Cache_Memcache::$_tags_changed)
		{
			$this->_memcache->set(Cache_Memcache::CACHE_TAG_KEY, Cache_Memcache::$_tag_cache, $this->_flags, 0);

			Cache_Memcache::$_tags_changed = FALSE;
		}
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
	 * @param array $tags [Optional]
	 * @return boolean
	 * @access public
	 * @throws Cache_Exception
	 */
	public function set($id, $data, $lifetime = NULL, array $tags = NULL)
	{
		// If tags are being set, but disabled, get rid of them
		if ( ! Cache_Memcache::$_tagging and $tags)
			throw new Cache_Exception('Trying to set using tags when tagging is disabled!');

		// If there are tags, process them
		if (Cache_Memcache::$_tagging and $tags)
		{
			foreach ($tags as $tag)
				Cache_Memcache::$_tag_cache[$tag][$id] = $id;

			Cache_Memcache::$_tags_changed = TRUE;
		}

		// Normalise the lifetime
		if (NULL === $lifetime)
			$lifetime = 0;
		else
			$lifetime += time();

		// Set the data to memcache
		return $this->_memcache->set($id, $data, $this->_flags, $lifetime);
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
		// Update tagging if required
		if (Cache_Memcache::$_tagging)
		{
			// Foreach tag, search for a matching id and remove
			foreach (Cache_Memcache::$_tag_cache as $tag => $_ids)
			{
				if (isset(Cache_Memcache::$_tag_cache[$tag][$id]))
				{
					unset(Cache_Memcache::$_tag_cache[$tag][$id]);
					Cache_Memcache::$_tags_changed = TRUE;
				}
			}
		}

		// Delete the id
		return $this->_memcache->delete($this->sanitize_id($id), $timeout);
	}

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param string $tag 
	 * @return boolean
	 * @access public
	 * @throws Cache_Exception
	 */
	public function delete_tag($tag, $timeout = 0)
	{
		// If tags are being set, but disabled, get rid of them
		if ( ! Cache_Memcache::$_tagging)
			throw new Cache_Exception('Trying to delete tags when tagging is disabled!');

		// Delete all items tagged to this value
		if (isset(Cache_Memcache::$_tag_cache[$tag]))
		{
			foreach (Cache_Memcache::$_tag_cache[$tag] as $id)
			{
				$this->_memcache->delete($id, $timeout);
				unset(Cache_Memcache::$_tag_cache[$tag][$id]);
			}

			// Update the tagging
			Cache_Memcache::$_tags_changed = TRUE;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Delete all cache entries
	 *
	 * @return boolean
	 * @access public
	 */
	public function delete_all()
	{
		if (Cache_Memcache::$_tagging)
		{
			Cache_Memcache::$_tag_cache = array();
			Cache_Memcache::$_tags_changed = TRUE;
		}

		$result = $this->_memcache->flush();

		// We must sleep after flushing, or overwriting will not work!
		// @see http://php.net/manual/en/function.memcache-flush.php#81420
		sleep(1);

		return $result;
	}

	/**
	 * Find cache entries based on a tag
	 *
	 * @param string $tag 
	 * @return array
	 * @access public
	 * @throws Cache_Exception
	 */
	public function find($tag)
	{
		// If tags are being set, but disabled, get rid of them
		if ( ! Cache_Memcache::$_tagging)
			throw new Cache_Exception('Trying to find tag/s when tagging is disabled!');

		if (isset(Cache_Memcache::$_tag_cache[$tag]) and $result = $this->_memcache->get(Cache_Memcache::$_tag_cache[$tag]))
			return $result;
		else
			return array();
	}
}