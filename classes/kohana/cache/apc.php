<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache APC Driver
 * 
 * Requires PHP-APC
 * 
 * @package    Kohana
 * @category   Cache
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Cache_Apc extends Cache {

	/**
	 * Check for existence of the APC extension
	 *
	 * @param  array     configuration
	 * @throws  Kohana_Cache_Exception
	 */
	protected function __construct(array $config)
	{
		if ( ! extension_loaded('apc'))
		{
			throw new Kohana_Cache_Exception('PHP APC extension is not available.');
		}

		parent::__construct($config);
	}

	/**
	 * Retrieve a value based on an id
	 *
	 * @param   string   id 
	 * @param   string   default [Optional] Default value to return if id not found
	 * @return  mixed
	 */
	public function get($id, $default = NULL)
	{
		return (($data = apc_fetch($this->_sanitize_id($id))) === FALSE) ? $default : $data;
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
		if ($lifetime === NULL)
		{
			$lifetime = Arr::get($this->_config, 'default_expire', Cache::DEFAULT_EXPIRE);
		}

		return apc_store($this->_sanitize_id($id), $data, $lifetime);
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
		return apc_delete($this->_sanitize_id($id));
	}

	/**
	 * Delete all cache entries
	 *
	 * @return  boolean
	 */
	public function delete_all()
	{
		return apc_clear_cache('user');
	}
}