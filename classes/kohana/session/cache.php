<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cache-based session class.
 *
 * @package    Kohana/Database
 * @category   Session
 * @author     Taai (https://github.com/taai)
 * @license    http://kohanaphp.com/license
 */
class Kohana_Session_Cache extends Session {
	
	// Cache instance
	protected $_cache;

	// The current session id
	protected $_session_id;

	// Prefix of the cache key
	protected $_prefix = 'session_';

	// TTL for session (not COOKIE)
	protected $_expires = NULL;

	// Serialize using JSON (to play nice with other programs)
	protected $_serialize_json = FALSE;
	
	
	public function __construct(array $config = NULL, $id = NULL)
	{
		if ( ! isset($config['group']))
		{
			// Use the default group
			$config['group'] = NULL;
		}
		
		if (isset($config['prefix']))
		{
			// Set the cache key prefix
			$this->_prefix = (string) $config['prefix'];
		}
		
		if (isset($config['expires']))
		{
			// Set the session TTL
			$this->_expires = (int) $config['expires'];
		}
		
		if (isset($config['serialize_json']))
		{
			// Serialize using JSON
			$this->_serialize_json = (bool) $config['serialize_json'];
		}
		
		// Load the cache
		$this->_cache = Cache::instance($config['group']);
		
		parent::__construct($config, $id);
		
		if (method_exists($this->_cache, 'garbage_collect'))
		{
			$default_expire = (int) Arr::get($this->_cache->config(), 'default_expire', Cache::DEFAULT_EXPIRE);
			
			if (mt_rand(0, $default_expire) === $default_expire)
			{
				// Run garbage collection
				// This will average out to run once every X requests
				$this->_cache->garbage_collect();
			}
		}
	}
	
	/**
	 * Get the current session id
	 * 
	 * @return  string
	 */
	public function id()
	{
		return $this->_session_id;
	}
	
	/**
	 * Loads existing session data.
	 *
	 *     $session->read();
	 *
	 * @param   string   session id
	 * @return  void
	 */
	public function read($id = NULL)
	{
		if (is_string($data = $this->_read($id)))
		{
			try
			{
				if ($this->_encrypted)
				{
					// Decrypt the data using the default key
					$data = Encrypt::instance($this->_encrypted)->decode($data);
				}
				else
				{
					// Decode the base64 encoded data
					$data = base64_decode($data);
				}

				// Unserialize the data
				$data = $this->_serialize_json ? json_decode($data, TRUE) : unserialize($data);
			}
			catch (Exception $e)
			{
				// Ignore all reading errors
			}
		}

		if (is_array($data))
		{
			// Load the data locally
			$this->_data = $data;
		}
	}
	
	/**
	 * Loads the raw session data string and returns it.
	 *
	 * @param   string  session id
	 * @return  null
	 */
	protected function _read($id = NULL)
	{
		if ($id OR $id = Cookie::get($this->_name))
		{
			if ($data = $this->_cache->get($this->_prefix . $id))
			{
				// Set the current session id
				$this->_session_id = $id;
				
				// Return the contents
				return $data;
			}
		}
		
		// Create a new session id
		$this->_regenerate();

		return NULL;
	}

	/**
	 * Generate a new session id and return it.
	 *
	 * @return  string
	 */
	protected function _regenerate()
	{
		do
		{
			// Create a new session id
			$id = str_replace('.', '-', uniqid(NULL, TRUE));
		}
		while ($this->_cache->get($this->_prefix . $id));
		
		return $this->_session_id = $id;
	}

	/**
	 * Writes the current session.
	 *
	 * @return  bool
	 */
	protected function _write()
	{
		// save session array
		if ($this->_cache->set($this->_prefix . $this->_session_id, $this->__toString(), $this->_expires))
		{
			// Update the cookie with the new session id
			Cookie::set($this->_name, $this->_session_id, $this->_lifetime);
			
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Restarts the current session.
	 *
	 * @return  boolean
	 */
	protected function _restart()
	{
		return TRUE;
	}

	/**
	 * Destroys the current session.
	 *
	 * @return  bool
	 */
	protected function _destroy()
	{
		if ($this->_cache->delete($this->_prefix . $this->_session_id))
		{
			// Delete the cookie
			Cookie::delete($this->_name);
			
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Session object is rendered to a serialized string. If encryption is
	 * enabled, the session will be encrypted. If not, the output string will
	 * be encoded using [base64_encode].
	 *
	 *     echo $session;
	 *
	 * @return  string
	 * @uses    Encrypt::encode
	 */
	public function __toString()
	{
		// Serialize the data array
		$data = $this->_serialize_json ? json_encode($this->_data) : serialize($this->_data);

		if ($this->_encrypted)
		{
			// Encrypt the data using the default key
			$data = Encrypt::instance($this->_encrypted)->encode($data);
		}
		else
		{
			// Obfuscate the data with base64 encoding
			$data = base64_encode($data);
		}

		return $data;
	}

} // End Kohana_Session_Cache
