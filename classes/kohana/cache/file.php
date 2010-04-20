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

	/**
	 * Retrieve a value based on an id
	 *
	 * @param   string   id 
	 * @param   string   default [Optional] Default value to return if id not found
	 * @return  mixed
	 */
	public function get($id, $default = NULL)
	{
		return (($data = Kohana::cache($this->sanitize_id($id))) === FALSE) ? $default : $data;
	}

	/**
	 * Set a value based on an id. Optionally add tags.
	 * 
	 * @param   string   id 
	 * @param   string   data 
	 * @param   integer  lifetime [Optional]
	 * @return  boolean
	 */
	public function set($id, $data, $lifetime = NULL)
	{
		if (NULL === $lifetime)
			$lifetime = $this->_default_expire;

		return Kohana::cache($this->sanitize_id($id), $data, $lifetime);
	}

	/**
	 * Delete a cache entry based on id
	 *
	 * @param   string   id 
	 * @param   integer  timeout [Optional]
	 * @return  boolean
	 */
	public function delete($id)
	{
		return unlink($this->filename($this->sanitize_id($id)));
	}

	/**
	 * Delete all cache entries
	 *
	 * @return  boolean
	 */
	public function delete_all()
	{
		return $this->delete_file(Kohana::$cache_dir, TRUE);
	}

	/**
	 * Create filename based on id string, just in case core changes
	 *
	 * @param	string	$id
	 * @return	string
	 */
	private function filename($id)
	{
		return sha1($id).'.txt';
	}

	/**
	 * Deletes files recursively and returns FALSE on any errors
	 *
	 * @param	string	$str
	 * @param	bool	$status
	 * @return	bool
	 */
	private function delete_file($str, $status)
	{
		// set current to true
		$current = TRUE;

		// if str is a file
		if (is_file($str))
		{
			$current = @unlink($str);
		}
		elseif (is_dir($str))
		{
			$files = new DirectoryIterator($str);
			// iterate over files in a directory
			while ($files->valid())
			{
				$name = trim($files->getFilename());
				// make sure file is valid
				if (($name != '.') && ($name != '..'))
					$this->delete_file($files->getPathname(), $status);
				// next file
				$files->next();
			}
			// make sure we are not deleting main cache dir
			if ($str != Kohana::$cache_dir)
				$current = @rmdir($str);
			// unset directory
			unset($files);
		}

		// determine if any errors have occurred while deleting
		return (($current === TRUE) AND ($status === TRUE)) ? TRUE : FALSE;
	}
}