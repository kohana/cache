<?php
include_once('CacheBasicMethodsTest.php');

/**
*  @package    Kohana/Cache/Memcache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2010 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_FileTest extends Kohana_CacheBasicMethodsTest {

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

		$this->cache(Cache::instance('file'));
	}

	/**
	 * Tests that ignored files are not removed from file cache
	 *
	 * @return  void
	 */
	public function test_ignore_delete_file()
	{
		$cache = $this->cache();
		$config = Kohana::config('cache')->get('file');
		$file = $config['cache_dir'].'/.gitignore';

		// Lets pollute the cache folder
		file_put_contents($file, 'foobar');

		$this->assertTrue($cache->delete_all());
		$this->assertTrue(file_exists($file));
		$this->assertEquals('foobar', file_get_contents($file));

		unlink($file);
	}

} // End Kohana_SqliteTest