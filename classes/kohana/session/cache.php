<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Session_Cache extends Session {

	// _memcached instance
	protected $_memcached;

	// The current session id
	protected $_session_id;

	// The old session id
	protected $_update_id;

	public function __construct(array $config = null, $id = NULL)
	{
		if ( ! isset($config['group']))
		{
			$config['group'] = 'memcache';
		}

		$this->_db = Cache::instance($config['group']);

		parent::__construct($config, $id);
	}

	public function id()
	{
		return $this->_session_id;
	}

	protected function _read($id = NULL)
	{	
		if (! is_null($id) OR $id = Cookie::get($this->_name))
		{
			$ret = $this->_db->get($id);
			if(false !== $ret)
			{
				$this->_session_id = $this->_update_id = $id;
				return $ret;
			}
		}

		$this->_regenerate();

		return NULL;
	}

	protected function _regenerate()
	{
		do
		{
			$id = str_replace('.', '', uniqid(NULL, TRUE));

			$ret = $this->_db->get($id);	
		}
		while (! is_null($ret));

		return $this->_session_id = $id;
	}

	protected function _write()
	{
		$contents = $this->__toString();
	
		$this->_db->set($this->_session_id, $contents, $this->_lifetime);

		$this->_update_id = $this->_session_id;

		Cookie::set($this->_name, $this->_session_id, $this->_lifetime);

		return TRUE;
	}

	protected function _destroy()
	{
		$ret = $this->_db->delete($this->_update_id);

		return $ret;
	}

} // End Session_Memcached
