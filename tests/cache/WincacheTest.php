<?php
include_once('CacheBasicMethodsTest.php');

/**
*  @package    Kohana/Cache/Memcache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_WincacheTest extends Kohana_CacheBasicMethodsTest {

	/**
	 * This method MUST be implemented by each driver to setup the `Cache`
	 * instance for each test.
	 * 
	 * This method should do the following tasks for each driver test:
	 * 
	 *  - Test the Cache instance driver is available, skip test otherwise
	 *  - Setup the Cache instance
	 *  - Call the parent setup method, `parent::setUp()`
	 *
	 * @return  void
	 */
	public function setUp()
	{
		parent::setUp();

		if ( ! extension_loaded('wincache'))
		{
			$this->markTestSkipped('APC PHP Extension is not available');
		}

		if (ini_get('apc.enable_cli') != '1')
		{
			$this->markTestSkipped('Unable to test APC in CLI mode. To fix '.
				'place "apc.enable_cli=1" in your php.ini file');
		}

		$this->cache(Cache::instance('wincache'));
	}

} // End Kohana_WincacheTest