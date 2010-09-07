<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [Kohana Cache](api/Kohana_Cached) Memcached driver,
 * 
 * ### Supported cache engines
 * 
 * *  [Memcache](http://www.php.net/manual/en/book.memcached.php)
 * 
 * ### Configuration example
 * 
 * Below is an example of a _memcached_ server configuration.
 * 
 *     return array(
 *          'default'   => array(                          // Default group
 *                  'driver'         => 'memcached',        // using Memcached driver
 *                  'servers'        => array(             // Available server definitions
 *                         // First memcached server
 *                         array(
 *                              'host'             => 'localhost',
 *                              'port'             => 11211,
 *                              'weight'           => 1,
 *                         ),
 *                         // Second memcached server
 *                         array(
 *                              'host'             => '192.168.1.5',
 *                              'port'             => 22122,
 *                              'weight'           => 1,
 *                         )
 *                  ),
 *                  'compression'                  => FALSE,   // Use compression?
 * 
 *                  'persistent_id'                => FALSE,   // persistent connection
 *                  'connect_timeout'              => 1000,    // connection timeout in ms
 *                  'retry_timeout'                => 0,       // time to wait until retrying a failed connection attempt, in seconds
 *                  'send_timeout'                 => 0,       // sending data timeout in microsec
 *                  'recv_timeout'                 => 0,       // receiving data timeout in microsec
 *                  'poll_timeout'                 => 1000,    // connection polling timeout in ms
 *                  'dns_cache'                    => FALSE,   // Cache DNS lookups
 *                  'server_failure_limit'         => 0,       // limit for server connection attempts
 *           ),
 *     )
 * 
 * In cases where only one cache group is required, if the group is named `default` there is
 * no need to pass the group name when instantiating a cache instance.
 * 
 * #### General cache group configuration settings
 * 
 * Below are the settings available to all types of cache driver.
 * 
 * Name           | Required | Description
 * -------------- | -------- | ---------------------------------------------------------------
 * driver         | __YES__  | (_string_) The driver type to use
 * servers        | __YES__  | (_array_) Associative array of server details, must include a __host__ key. (see _Memcache server configuration_ below)
 * compression    | __NO__   | (_boolean_) Use data compression when caching
 * 
 * #### Memcache server configuration
 * 
 * The following settings should be used when defining each memcache server
 * 
 * Name             | Required | Description
 * ---------------- | -------- | ---------------------------------------------------------------
 * host             | __YES__  | (_string_) The host of the memcache server, i.e. __localhost__; or __127.0.0.1__; or __memcache.domain.tld__
 * port             | __NO__   | (_integer_) Point to the port where memcached is listening for connections. Set this parameter to 0 when using UNIX domain sockets.  Default __11211__
 * weight           | __NO__   | (_integer_) Number of buckets to create for this server which in turn control its probability of it being selected. The probability is relative to the total weight of all servers. Default __1__
 * 
 * ### System requirements
 * 
 * *  Kohana 3.0.x
 * *  PHP 5.2.4 or greater
 * *  Memcached
 * *  libmemcached
 * *  Zlib
 * 
 * @package    Kohana
 * @category   Cache
 * @version    1.0
 * @author     Scott Jungwirth
 * @license    http://kohanaphp.com/license
 */
class Kohana_Cache_Memcached extends Cache {

	// Memcache has a maximum cache lifetime of 30 days
	const CACHE_CEILING = 2592000;

	/**
	 * Memcache resource
	 *
	 * @var Memcached
	 */
	protected $_memcached;

	/**
	 * Constructs the memcache Kohana_Cache object
	 *
	 * @param   array     configuration
	 * @throws  Kohana_Cache_Exception
	 */
	protected function __construct(array $config)
	{
		// Check for the memcached extention
		if ( ! extension_loaded('memcached'))
		{
			throw new Kohana_Cache_Exception('Memcached PHP extention not loaded');
		}

		parent::__construct($config);

		// Setup Memcache
		if ($persistent_id = Arr::get($this->_config, 'persistent_id')) {
			$this->_memcached = new Memcached($persistent_id);
		} else {
			$this->_memcached = new Memcached;
		}

		// Setup the options
		
		// set compression
		$this->_memcached->setOption(Memcached::OPT_COMPRESSION, Arr::get($this->_config, 'compression', FALSE));
		
		// use igbinary for serialization if available
		$this->_memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::HAVE_IGBINARY ? Memcached::SERIALIZER_IGBINARY : Memcached::SERIALIZER_PHP);
		
		// use murmur hash to hash
		$this->_memcached->setOption(Memcached::OPT_HASH, Memcached::HASH_MURMUR);
		
		// consistent hashing, even between clients (python, etc)
		$this->_memcached->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
		$this->_memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, TRUE);
		
		// advanced options
		$this->_memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, FALSE); // not working at the moment
		//Memcached::OPT_BUFFER_WRITES, FALSE
		//Memcached::OPT_NO_BLOCK, FALSE
		//Memcached::OPT_TCP_NODELAY
		
		//Memcached::OPT_SOCKET_SEND_SIZE
		//Memcached::OPT_SOCKET_RECV_SIZE
		
		// timeouts
		$this->_memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, Arr::get($this->_config, 'connect_timeout', 1000));
		$this->_memcached->setOption(Memcached::OPT_RETRY_TIMEOUT, Arr::get($this->_config, 'retry_timeout', 0));
		$this->_memcached->setOption(Memcached::OPT_SEND_TIMEOUT, Arr::get($this->_config, 'send_timeout', 0));
		$this->_memcached->setOption(Memcached::OPT_RECV_TIMEOUT, Arr::get($this->_config, 'recv_timeout', 0));
		$this->_memcached->setOption(Memcached::OPT_POLL_TIMEOUT, Arr::get($this->_config, 'poll_timeout', 1000));
		
		// cache dns lookups
		$this->_memcached->setOption(Memcached::OPT_CACHE_LOOKUPS, Arr::get($this->_config, 'dns_cache', TRUE));
		
		// failure limit
		$this->_memcached->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, Arr::get($this->_config, 'server_failure_limit', 0));
		
		// Load servers from configuration
		$servers = Arr::get($this->_config, 'servers', NULL);

		if ( ! $servers)
		{
			// Throw an exception if no server found
			throw new Kohana_Cache_Exception('No Memcached servers defined in configuration');
		}

		// Setup default server configuration
		$config = array(
			'host'             => 'localhost',
			'port'             => 11211,
			'weight'           => 1,
		);

		// Add the memcache servers to the pool
		foreach ($servers as &$server)
		{
			// Merge the defined config with defaults
			$server += $config;
		}
		unset($server);
		
		if (!$persistent_id || !count($this->_memcached->getServerList())) {
			
			if ( ! $this->_memcached->addServers($servers))
			{
				throw new Kohana_Cache_Exception('Memcache could not connect to host \':host\' using port \':port\'', array(':host' => $server['host'], ':port' => $server['port']));
			}
		}
		
	}

	/**
	 * Retrieve a cached value entry by id.
	 * 
	 *     // Retrieve cache entry from memcache group
	 *     $data = Cache::instance('memcache')->get('foo');
	 * 
	 *     // Retrieve cache entry from memcache group and return 'bar' if miss
	 *     $data = Cache::instance('memcache')->get('foo', 'bar');
	 *
	 * @param   mixed   id of cache entry or array of ids
	 * @param   string   default value to return if cache miss
	 * @return  mixed
	 * @throws  Kohana_Cache_Exception
	 */
	public function get($id, $default = NULL)
	{
		if (!is_array($id)) {
			// Get the value from Memcache
			$value = $this->_memcached->get($this->_sanitize_id($id));
			
			// If the value wasn't found (or error), normalise it
			if ($value === FALSE && $this->_memcached->getResultCode() != Memcached::RES_SUCCESS)
			{
				$value = (NULL === $default) ? NULL : $default;
			}
			
			// Return the value
			return $value;
		}
		
		// else
		foreach ($id as &$i) {
			// sanitize the IDs
			$i = $this->_sanitize_id($i);
		}
		unset($i);
		
		// multi-get from memcache
		return $this->_memcached->getMulti($id);
	}

	/**
	 * Set a value to cache with id and lifetime
	 * 
	 *     $data = 'bar';
	 * 
	 *     // Set 'bar' to 'foo' in memcache group for 10 minutes
	 *     if (Cache::instance('memcache')->set('foo', $data, 600))
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
		// Set the data to memcache
		return $this->_memcached->set($this->_sanitize_id($id), $data, Cache_Memcached::get_expiration($lifetime));
	}

	/**
	 * Delete a cache entry based on id
	 * 
	 *     // Delete the 'foo' cache entry immediately
	 *     Cache::instance('memcache')->delete('foo');
	 * 
	 *     // Delete the 'bar' cache entry after 30 seconds
	 *     Cache::instance('memcache')->delete('bar', 30);
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
	 *     Cache::instance('memcache')->delete_all();
	 *
	 * @return  boolean
	 */
	public function delete_all()
	{
		return $this->_memcached->flush();
	}
	
	/**
	 * normalize cache lifetime, converting everything to a unix timestamp expiration
	 * 
	 * @static
	 * @param  int  $lifetime
	 * @return int
	 */
	protected static function get_expiration($lifetime) {
		// If the lifetime is greater than the ceiling
		if ($lifetime > Cache_Memcache::CACHE_CEILING)
		{
			// Set the lifetime to maximum cache time
			$lifetime = Cache_Memcache::CACHE_CEILING + time();
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
		
		return $lifetime;
	}
}

