<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache File Driver
 * 
 * @package    Kohana
 * @category   Cache
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Cache_File extends Cache {

	// Default cache directory
	const CACHE_DIR = APPPATH.'/cache/.kohana_file_cache';

	/**
	 * Creates a hashed filename based on the string
	 *
	 * @param   string   string to hash into filename
	 * @return  string
	 */
	protected static function filename($string)
	{
		return sha1($string).'.txt';
	}

	/**
	 * Resolves the cache directory location
	 *
	 * @param   string   filename to resolve
	 * @return  string
	 */
	protected static function resolve_directory($filename)
	{
		return $this->_cache_dir->getRealPath().DIRECTORY_SEPARATOR.$filename[0].$filename[1].DIRECTORY_SEPARATOR;
	}

	/**
	 * The cache directory
	 *
	 * @var  string   the caching directory
	 */
	protected $_cache_dir;

	/**
	 * Constructs the file cache driver
	 *
	 * @param   array    config 
	 * @throws  Kohana_Cache_Exception
	 */
	protected function __construct(array $config)
	{
		$this->_cache_dir = new DirectoryIterator(Arr::get($config, 'cache_dir', Cache_File::CACHE_DIR));

		// If the defined directory is a file, get outta here
		if ($this->_cache_dir->isFile())
		{
			throw new Kohana_Cache_Exception('Unable to create cache directory as a already file exists : :resource', array(':resource' => $this->_cache_dir->getRealPath()));
		}

		// If the defined directory does not exist, create it
		if ( ! $this->_cache_dir->isDir())
		{
			if ( ! mkdir($this->_cache_dir->getRealPath(), 0777, TRUE))
			{
				throw new Kohana_Cache_Exception('Failed to create the defined cache directory : :resource', array(':resource' => $this->_cache_dir->getRealPath()));
			}

			// Manage unmask issues
			chmod($this->_cache_dir->getRealPath(), 0777);
		}

		// Check the read status of the directory
		if ( ! $this->_cache_dir->isReadable())
		{
			throw new Kohana_Cache_Exception('Unable to read from the cache directory :resource', array(':resource' => $this->_cache_dir->getRealPath());
		}

		// Check the write status of the directory
		if ( ! $this->_cache_dir->isWritable())
		{
			throw new Kohana_Cache_Exception('Unable to write to the cache directory :resource', array(':resource' => $this->_cache_dir->getRealPath());
		}
	}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param   string   id 
	 * @param   string   default [Optional] Default value to return if id not found
	 * @return  mixed
	 * @throws  Kohana_Cache_Exception|ErrorException
	 */
	public function get($id, $default = NULL)
	{
		$filename = Cache_File::filename($this->sanitize_id($id));
		$directory = Cache_File::resolve_directory($filename);

		// Wrap operations in try/catch to handle notices
		try
		{
			// Open file
			$file = new SplFileInfo($directory.$filename);

			// If file does not exist
			if ( ! $file->isFile())
			{
				// Return default value
				return $default;
			}
			else
			{
				// If the cache entry has expired
				if ($file->getMTime() < (time() - Arr::get($this->_config, 'default_expire', Cache::DEFAULT_EXPIRE)))
				{
					// Delete the file
					try
					{
						unlink($file->getRealPath());
					}
					catch (Exception $e)
					{
						// File has already been removed
						return $default;
					}

					// Return default value
					return $default;
				}
				else
				{
					// Return unserialized object
					return unserialize(file_get_contents($file->getRealPath()));
				}
			}
			
		}
		catch (ErrorException $e)
		{
			// Handle ErrorException caused by failed unserialization
			if ($e->getCode() === E_NOTICE)
			{
				throw new Kohana_Cache_Exception(__METHOD__.' failed to unserialize cached object with message : '.$e->getMessage());
			}

			// Otherwise throw the exception
			throw $e;
		}
	}

	/**
	 * Set a value based on an id. Optionally add tags.
	 * 
	 * @param   string   id 
	 * @param   string   data 
	 * @param   integer  lifetime [Optional]
	 * @return  boolean
	 * @throws  Kohana_Cache_Exception|ErrorException
	 */
	public function set($id, $data, $lifetime = NULL)
	{
		$filename = Cache_File::filename($this->sanitize_id($id));
		$directory = Cache_File::resolve_directory($filename);

		// Open directory
		$dir = new SplFileInfo($directory);

		// If the directory path is not a directory
		if ( ! $dir->isDir())
		{
			// Create the directory 
			if ( ! mkdir($directory, 0777, TRUE))
			{
				throw new Kohana_Cache_Exception(__METHOD__.' unable to create directory : :directory', array(':directory' => $directory));
			}

			// chmod to solve potential umask issues
			chmod($directory, 0777);
		}

		// Open file to inspect
		$file = new SplFileInfo($directory.$filename);

		// If it is a directory or not writable
		if ( ! $file->isWritable())
		{
			// Throw an exception
			throw new Kohana_Cache_Exception(__METHOD__.' unable to set data to cache. Either the resource is a directory or the file is not writable : :file', array(':file' => $file->getRealPath()));
		}

		try
		{
			// Serialize the data
			$data = serialize($data);
		}
		catch (ErrorException $e)
		{
			// If serialize through an error exception
			if ($e->getCode() === E_NOTICE)
			{
				// Throw a caching error
				throw new Kohana_Cache_Exception(__METHOD__.' failed to serialize data for caching with message : '.$e->getMessage());
			}

			// Else rethrow the error exception
			throw $e;
		}

		try
		{
			return (bool) file_put_contents($file->getRealPath(), $data);
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param   string   id 
	 * @param   integer  timeout [Optional]
	 * @return  void|boolean
	 */
	public function delete($id)
	{
		$filename = Cache::filename($this->sanitize_id($id));
		$directory = Cache::resolve_directory($filename);

		$cache_entry = new SplFileInfo($directory.$filename);

		if ( ! $cache_entry->isFile())
		{
			return NULL;
		}

		try
		{
			return unlink($cache_entry->getRealPath());
		}
		catch (Exception $e)
		{
			return NULL;
		}
	}

	/**
	 * Delete all cache entries
	 *
	 * @return  boolean
	 */
	public function delete_all()
	{
		while ($this->_cache_dir->valid())
		{
			$this->_delete_file($this->_cache_dir, TRUE, TRUE);
			$this->_cache_dir->next();
		}

		return TRUE;
	}

	/**
	 * Deletes files recursively and returns FALSE on any errors
	 *
	 * @param   SplFileInfo  file
	 * @param   boolean  retain the parent directory
	 * @param   boolean  ignore_errors to prevent all exceptions interrupting exec
	 * @return  boolean
	 * @throws  Kohana_Cache_Exception
	 */
	protected function _delete_file(SplFileInfo $file, $retain_directory = FALSE, $ignore_errors = FALSE)
	{
		// Allow graceful error handling
		try
		{
			// If the file isn't writable or is a symbolic link
			if ( ! $this->isWritable() or $this->isLink())
			{
				// return
				return FALSE;
			}
			// If the file is self or parent reference
			else if ($file->isDot())
			{
				// return
				return TRUE;
			}
			// If is a file
			else if ($file->isFile())
			{
				// Try to delete the file
				try
				{
					return unlink($file->getRealPath())
				}
				catch (ErrorException $e)
				{
					// Return gracefully if cannot delete normally
					if ($e->getCode() === E_WARNING)
					{
						throw new Kohana_Cache_Exception(__METHOD__.' unable to remove file : :file', array(':file' => $file->getRealPath()));
					}
					throw $e;
				}
			}
			// If is directory
			else if ($file->isDir())
			{
				// Loop over the directory
				while ($file->valid())
				{
					// Remove each file and move pointer on
					$this->_delete_file($file->current());
					$file->next();
				}

				// If retain directory, return without deleting
				if ($retain_directory)
				{
					return TRUE;
				}

				// Try to remove folder
				try
				{
					return rmdir($file->getRealPath())
				}
				catch (ErrorException $e)
				{
					// Return gracefully if cannot delete normally
					if ($e->getCode() === E_WARNING)
					{
						throw new Kohana_Cache_Exception(__METHOD__.' unable to remove directory : :directory', array(':directory' => $file->getRealPath()));
					}

					throw $e;
				}
			}
		}
		catch (Exception $e)
		{
			if ($ignore_errors === TRUE)
			{
				return FALSE;
			}

			throw $e;
		}
	}
}