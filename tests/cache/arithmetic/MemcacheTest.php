<?php
include_once(Kohana::find_file('tests/cache/arithmetic', 'CacheArithmeticMethods'));

/**
*  @package    Kohana/Cache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2011 Kohana Team
 * @license    http://kohanaphp.com/license
 */

class Kohana_CacheArithmeticMemcacheTest extends Kohana_CacheArithmeticMethodsTest {


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

		if ( ! extension_loaded('memcache'))
		{
			$this->markTestSkipped('Memcache PHP Extension is not available');
		}

		if ( ! $config = Kohana::config('cache')->get('memcache'))
		{
			$this->markTestSkipped('Unable to load Memcache configuration');
		}

		$memcache = new Memcache;
		if ( ! $memcache->connect($config['servers'][0]['host'], 
			$config['servers'][0]['port']))
		{
			$this->markTestSkipped('Unable to connect to memcache server @ '.
				$config['servers'][0]['host'].':'.
				$config['servers'][0]['port']);
		}

		if ($memcache->getVersion() === FALSE)
		{
			$this->markTestSkipped('Memcache server @ '.
				$config['servers'][0]['host'].':'.
				$config['servers'][0]['port'].
				' not responding!');
		}

		unset($memcache);

		$this->cache(Cache::instance('memcache'));
	}

}