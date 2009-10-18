<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Kohana Cache
 * 
 * Caching library for Kohana PHP 3
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
abstract class Kohana_Cache {

	/**
	 * Retrieve a value based on an id
	 *
	 * @param string $id 
	 * @param string $default [Optional] Default value to return if id not found
	 * @return mixed
	 * @access public
	 * @abstract
	 */
	abstract public function get($id, $default = NULL);

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
	 * @abstract
	 */
	abstract public function set($id, $data, $lifetime = NULL, array $tags = NULL);

	/**
	 * Delete a cache entry based on id
	 *
	 * @param string $id 
	 * @param integer $timeout [Optional]
	 * @return boolean
	 * @access public
	 * @abstract
	 */
	abstract public function delete($id, $timeout = 0);

	/**
	 * Delete cache entries based on a tag
	 *
	 * @param string $tag 
	 * @param integer $timeout [Optional]
	 * @return boolean
	 * @access public
	 * @abstract
	 */
	abstract public function delete_tag($tag, $timeout = 0);

	/**
	 * Delete all cache entries
	 *
	 * @return boolean
	 * @access public
	 * @abstract
	 */
	abstract public function delete_all();

	/**
	 * Find cache entries based on a tag
	 *
	 * @param string $tag 
	 * @return mixed
	 * @access public
	 * @abstract
	 */
	abstract public function find($tag);

	/**
	 * Replaces troublesome characters with underscores.
	 *
	 * @param string $id
	 * @return string
	 * @access protected
	 */
	protected function sanitize_id($id)
	{
		// Change slashes and spaces to underscores
		return str_replace(array('/', '\\', ' '), '_', $id);
	}
}
// End Kohana_Cache