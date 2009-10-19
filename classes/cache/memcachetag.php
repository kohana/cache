<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Kohana Cache Memcached-tags Driver
 * 
 * Memcached-tags extension provides native tagging support to
 * memcache.
 * 
 * @see http://code.google.com/p/memcached-tags/
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
class Cache_MemcacheTag extends Cache_Memcache implements Cache_Tagging {

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
			Cache_Memcache::$_instance = new Cache_MemcacheTag;

		return Cache_Memcache::$_instance;
	}

	/**
	 * Constructs the memcache object
	 *
	 * @access protected
	 * @throws Cache_Exception
	 */
	protected function __construct()
	{
		parent::__construct();

		if ( ! method_exists($this->_memcache, 'tag_add'))
			throw new Cache_Exception('Memcached-tags PHP plugin not present. Please see http://code.google.com/p/memcached-tags/ for more information');
	}

	/**
	 * Set a value based on an id with tags
	 * 
	 * @param string $id 
	 * @param string $data 
	 * @param integer $lifetime [Optional]
	 * @param array $tags [Optional]
	 * @return boolean
	 * @access public
	 */
	public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL)
	{
		$result = $this->set($id, $data, $lifetime);

		if ($result and $tags)
		{
			foreach ($tags as $tag)
				$this->_memcache->tag_add($tag, $id);
		}

		return $result;
	}

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param string $tag 
	 * @return boolean
	 * @access public
	 */
	public function delete_tag($tag)
	{
		return $this->_memcache->tag_delete($tag);
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
		throw new Cache_Exception('Memcached-tags does not support finding by tag');
	}	
}