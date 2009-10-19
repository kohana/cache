<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache Sqlite Driver
 * 
 * Requires SQLite3 and PDO
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
class Cache_Sqlite extends Cache implements Cache_Tagging {

	/**
	 * Object singleton
	 *
	 * @var Cache_Sqlite
	 */
	static protected $_instance;

	/**
	 * Singleton creator
	 *
	 * @return Cache_Sqlite
	 * @access public
	 * @static
	 */
	static public function instance()
	{
		if (NULL === Cache_Sqlite::$_instance)
			Cache_Sqlite::$_instance = new Cache_Sqlite;

		return Cache_Sqlite::$_instance;
	}

	/**
	 * Configuration
	 *
	 * @var Kohana_Config
	 */
	protected $_config;

	/**
	 * Database resource
	 *
	 * @var PDO
	 */
	protected $_db;

	/**
	 * Sets up the PDO SQLite table and
	 * initialises the PDO connection
	 *
	 * @access protected
	 */
	protected function __construct()
	{
		// Setup the Sqlite PDO
		$this->_config = Kohana::config('cache-sqlite');
		$this->_db = new PDO('sqlite:'.$this->_config->database);

		// Test for existing DB
		$result = $this->_db->query("SELECT * FROM sqlite_master WHERE name = 'caches' AND type = 'table'")->fetchAll();

		// If there is no table, create a new one
		if (0 == count($result))
		{
			try
			{
				// Create the caches table
				$this->_db->query($this->_config->schema);
			}
			catch (PDOException $e)
			{
				throw new Cache_Exception('Failed to create new SQLite caches table with the following error : :error', array(':error' => $e->getMessage()));
			}
		}
	}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param string $id 
	 * @param string $default [Optional] Default value to return if id not found
	 * @return mixed
	 * @access public
	 * @throws Cache_Exception
	 */
	public function get($id, $default = NULL)
	{
		// Prepare statement
		$statement = $this->_db->prepare('SELECT id, expiration, cache FROM caches WHERE id = :id LIMIT 0, 1');

		// Try and load the cache based on id
		try
		{
			$statement->execute(array(':id' => $id));
		}
		catch (PDOException $e)
		{
			throw new Cache_Exception('There was a problem querying the local SQLite3 cache. :error', array(':error' => $e->getMessage()));
		}

		if ( ! $result = $statement->fetch(PDO::FETCH_OBJ))
			return $default;

		// If the cache has expired
		if ($result->expiration != 0 and $result->expiration <= time())
		{
			// Delete it and return default value
			$this->delete($id);
			return $default;
		}
		// Otherwise return cached object
		else
		{
			// Disable notices for unserializing
			$ER = error_reporting(~E_NOTICE);
			
			// Return the valid cache data
			$data = unserialize($result->cache);

			// Turn notices back on
			error_reporting($ER);

			// Return the resulting data
			return $data;
		}
	}

	/**
	 * Set a value based on an id. Optionally add tags.
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
		return (bool) $this->set_with_tags($id, $data, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param string $id 
	 * @param integer $timeout [Optional]
	 * @return boolean
	 * @access public
	 */
	public function delete($id)
	{
		// Prepare statement
		$statement = $this->_db->prepare('DELETE FROM caches WHERE id = :id');

		// Remove the entry
		try
		{
			$statement->execute(array(':id' => $id));
		}
		catch (PDOException $e)
		{
			throw new Cache_Exception('There was a problem querying the local SQLite3 cache. :error', array(':error' => $e->getMessage()));
		}

		return (bool) $statement->rowCount();
	}

	/**
	 * Delete all cache entries
	 *
	 * @return boolean
	 * @access public
	 */
	public function delete_all()
	{
		// Prepare statement
		$statement = $this->_db->prepare('DELETE FROM caches');

		// Remove the entry
		try
		{
			$statement->execute();
		}
		catch (PDOException $e)
		{
			throw new Cache_Exception('There was a problem querying the local SQLite3 cache. :error', array(':error' => $e->getMessage()));
		}

		return (bool) $statement->rowCount();
	}

	/**
	 * Set a value based on an id. Optionally add tags.
	 * 
	 * @param string $id 
	 * @param string $data 
	 * @param integer $lifetime [Optional]
	 * @param array $tags [Optional]
	 * @return boolean
	 * @access public
	 * @throws Cache_Exception
	 */
	public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL)
	{
		// Serialize the data
		$data = serialize($data);

		// Normalise tags
		$tags = (NULL === $tags) ? NULL : '<'.implode('>,<', $tags).'>';

		// Setup lifetime
		if (NULL === $lifetime)
			$lifetime = (0 === $this->_config->default_expire) ? 0 : $this->_config->default_expire + time();
		else
			$lifetime = (0 === $lifetime) ? 0 : $lifetime + time();

		// Prepare statement
		$statement = $this->exists($id) ? $this->_db->prepare('UPDATE caches SET expiration = :expiration, cache = :cache, tags = :tags WHERE id = :id') : $this->_db->prepare('INSERT INTO caches (id, cache, expiration, tags) VALUES (:id, :cache, :expiration, :tags)');

		// Try to insert
		try
		{
			$statement->execute(array(':id' => $id, ':cache' => $data, ':expiration' => $lifetime, ':tags' => $tags));
		}
		catch (PDOException $e)
		{
			throw new Cache_Exception('There was a problem querying the local SQLite3 cache. :error', array(':error' => $e->getMessage()));
		}

		return (bool) $statement->rowCount();
	}

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param string $tag 
	 * @param integer $timeout [Optional]
	 * @return boolean
	 * @access public
	 */
	public function delete_tag($tag)
	{
		// Prepare the statement
		$statement = $this->_db->prepare('DELETE FROM caches WHERE tags LIKE :tag');

		// Try to delete
		try
		{
			$statement->execute(array(':tag' => "%<{$tag}>%"));
		}
		catch (PDOException $e)
		{
			throw new Cache_Exception('There was a problem querying the local SQLite3 cache. :error', array(':error' => $e->getMessage()));
		}

		return (bool) $statement->rowCount();
	}

	/**
	 * Find cache entries based on a tag
	 *
	 * @param string $tag 
	 * @return array
	 * @access public
	 */
	public function find($tag)
	{
		// Prepare the statement
		$statement = $this->_db->prepare('SELECT id, cache FROM caches WHERE tags LIKE :tag');

		// Try to find
		try
		{
			if ( ! $statement->execute(array(':tag' => "%<{$tag}>%")))
				return array();
		}
		catch (PDOException $e)
		{
			throw new Cache_Exception('There was a problem querying the local SQLite3 cache. :error', array(':error' => $e->getMessage()));
		}

		$result = array();

		while ($row = $statement->fetchObject())
		{
			// Disable notices for unserializing
			$ER = error_reporting(~E_NOTICE);

			$result[$row->id] = unserialize($row->cache);

			// Turn notices back on
			error_reporting($ER);
		}

		return $result;
	}

	/**
	 * Tests whether an id exists or not
	 *
	 * @param string $id 
	 * @return boolean
	 * @access protected
	 */
	protected function exists($id)
	{
		$statement = $this->_db->prepare('SELECT id FROM caches WHERE id = :id');
		$statement->execute(array(':id' => $id));
		return (bool) $statement->fetchAll();
	}
}