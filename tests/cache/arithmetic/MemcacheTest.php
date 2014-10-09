<?php
include_once(Kohana::find_file('tests/cache/arithmetic', 'CacheArithmeticMethods'));

/**
 * @package    Kohana/Cache/Memcache
 * @group      kohana
 * @group      kohana.cache
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) 2009-2012 Kohana Team
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
		if ( ! $config = Kohana::$config->load('cache.memcache'))
		{
			Kohana::$config->load('cache')
				->set(
					'memcache',
					array(
						'driver'             => 'memcache',
						'default_expire'     => 3600,
						'compression'        => FALSE,              // Use Zlib compression (can cause issues with integers)
						'servers'            => array(
							'local' => array(
								'host'             => 'localhost',  // Memcache Server
								'port'             => 11211,        // Memcache port number
								'persistent'       => FALSE,        // Persistent connection
								'weight'           => 1,
								'timeout'          => 1,
								'retry_interval'   => 15,
								'status'           => TRUE,
							),
						),
						'instant_death'      => TRUE,
					)
				);
			$config = Kohana::$config->load('cache.memcache');
		}

		$memcache = new Memcache;
		if ( ! $memcache->connect($config['servers']['local']['host'], 
			$config['servers']['local']['port']))
		{
			$this->markTestSkipped('Unable to connect to memcache server @ '.
				$config['servers']['local']['host'].':'.
				$config['servers']['local']['port']);
		}

		if ($memcache->getVersion() === FALSE)
		{
			$this->markTestSkipped('Memcache server @ '.
				$config['servers']['local']['host'].':'.
				$config['servers']['local']['port'].
				' not responding!');
		}

		unset($memcache);

		$this->cache(Cache::instance('memcache'));
	}

	/**
	 * Tests that multiple values set with Memcache do not cause unexpected
	 * results. For accurate results, this should be run with a memcache
	 * configuration that includes multiple servers.
	 * 
	 * This is to test #4110
	 *
	 * @link    http://dev.kohanaframework.org/issues/4110
	 * @return  void
	 */
	public function test_multiple_set()
	{
		$cache = $this->cache();
		$id_set = 'set_id';
		$ttl = 300;

		$data = array(
			'foobar',
			0,
			1.0,
			new stdClass,
			array('foo', 'bar' => 1),
			TRUE,
			NULL,
			FALSE
		);

		$previous_set = $cache->get($id_set, NULL);

		foreach ($data as $value)
		{
			// Use Equals over Sames as Objects will not be equal
			$this->assertEquals($previous_set, $cache->get($id_set, NULL));
			$cache->set($id_set, $value, $ttl);

			$previous_set = $value;
		}
	}

	/**
	 * ----------------------------------------------------------------------------------------
	 * Begin temporary override for expected HHVM issues
	 *
	 * The HHVM memcache extension has some behaviour incompatible with standard PHP - reported
	 * and fixed in an upcoming release. To avoid breaking the build, these failures will be
	 * converted to skipped tests for now.
	 *
	 * @todo - Revert this workaround once HHVM 3.4 is released and on Travis
	 * ----------------------------------------------------------------------------------------
	 */

	/**
	 * Tests the [Cache::set()] method, testing;
	 *
	 *  - The value is cached
	 *  - The lifetime is respected
	 *  - The returned value type is as expected
	 *  - The default not-found value is respected
	 *
	 * @dataProvider provider_set_get
	 *
	 * @param   array    $data
	 * @param   mixed    $expected
	 * @return  void
	 */
	public function test_set_get(array $data, $expected)
	{
		try
		{
			parent::test_set_get($data, $expected);
		}
		catch (PHPUnit_Framework_ExpectationFailedException $e)
		{
			$this->suppress_hhvm_memcached_failure_or_rethrow($e);
		}
	}

	/**
	 * Test for [Cache_Arithmetic::increment()]
	 *
	 * @dataProvider provider_increment
	 *
	 * @param   integer  start state
	 * @param   array    increment arguments
	 * @return  void
	 */
	public function test_increment($start_state = NULL, array $inc_args, $expected)
	{
		try
		{
			parent::test_increment($start_state, $inc_args, $expected);
		}
		catch (PHPUnit_Framework_ExpectationFailedException $e)
		{
			$this->suppress_hhvm_memcached_failure_or_rethrow($e);
		}
	}

	/**
	 * Test for [Cache_Arithmetic::decrement()]
	 *
	 * @dataProvider provider_decrement
	 *
	 * @param   integer  start state
	 * @param   array    decrement arguments
	 * @return  void
	 */
	public function test_decrement($start_state = NULL, array $dec_args, $expected)
	{
		try
		{
			parent::test_decrement($start_state, $dec_args, $expected);
		}
		catch (PHPUnit_Framework_ExpectationFailedException $e)
		{
			$this->suppress_hhvm_memcached_failure_or_rethrow($e);
		}
	}

	/**
	 * @param $e
	 * @throws
	 */
	protected function suppress_hhvm_memcached_failure_or_rethrow($e)
	{
		if (defined('HHVM_VERSION'))
		{
			$this->markTestSkipped('Skipped expected failure due to HHVM memcache issues - see https://github.com/kohana/cache/pull/52');
		}
		else
		{
			throw $e;
		}
	}

	/**
	 * End HHVM temporary workaround
	 */


} // End Kohana_CacheArithmeticMemcacheTest
