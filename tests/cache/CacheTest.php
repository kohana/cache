<?php

class Kohana_CacheTest extends PHPUnit_Framework_TestCase {

	const BAD_GROUP_DEFINITION  = 1010;
	const NO_DRIVER_DEFINITION = 1111;

	/**
	 * Tests that cache is not a singleton
	 * 
	 * @return  void
	 */
	public function test_not_singleton()
	{
		$cache = $this->getMockForAbstractClass('Cache', array(array()),
			'Cache_Mock_Not_Singleton');

		// Get the constructor method
		$constructorMethod = new ReflectionMethod($cache, '__construct');

		// Test the constructor for hidden visibility
		$this->assertTrue($constructorMethod->isPublic(), 
			'__construct is does not have protected visibility');
	}

	/**
	 * Data provider for test_factory
	 *
	 * @return  array
	 */
	public function provider_factory()
	{
		$tmp = realpath(sys_get_temp_dir());

		return array(
			// Test default group and config
			array(
				NULL,
				NULL,
				new Cache_File(Kohana::config('cache')->get('file'))
			),
			// Test default group and custom config
			array(
				NULL,
				array(
					'driver'     => 'file',
					'cache_dir'  => $tmp,
					
				),
				new Cache_File(array(
					'driver'     => 'file',
					'cache_dir'  => $tmp,
					
				))
			),
			// Test defined group and default config
			array(
				'file',
				NULL,
				new Cache_File(Kohana::config('cache')->get('file'))
			),
			// Test defined group and custom config
			array(
				'file',
				array(
					'driver'     => 'file',
					'cache_dir'  => $tmp,
				
				),
				new Cache_File(array(
					'driver'     => 'file',
					'cache_dir'  => $tmp
				))
			),
			// Test bad group definition with attempted loaded config
			array(
				Kohana_CacheTest::BAD_GROUP_DEFINITION,
				NULL,
				'Failed to load Kohana Cache group: 1010'
			),
			// Test group definition with no driver
			array(
				Kohana_CacheTest::NO_DRIVER_DEFINITION,
				array(
					'notdriver'  => 'NanunanuNoweaheorsmdndf'
				),
				'No cache driver configuration setting found!'
			)
		);
	}

	/**
	 * Tests the [Cache::factory()] method behaves as expected
	 * 
	 * @dataProvider provider_factory
	 *
	 * @return  void
	 */
	public function test_factory($group, $config, $expected)
	{
		if (in_array($group, array(
			Kohana_CacheTest::BAD_GROUP_DEFINITION,
			Kohana_CacheTest::NO_DRIVER_DEFINITION
			)
		))
		{
			$this->setExpectedException('Cache_Exception');
		}

		try
		{
			$cache = Cache::factory($group, $config);
		}
		catch (Cache_Exception $e)
		{
			$this->assertSame($expected, $e->getMessage());
			throw $e;
		}

		$this->assertInstanceOf(get_class($expected), $cache);
		$this->assertSame($expected->config(), $cache->config());
	}

	/**
	 * Data provider for test_config
	 *
	 * @return  array
	 */
	public function provider_config()
	{
		return array(
			array(
				array(
					'server'     => 'otherhost',
					'port'       => 5555,
					'persistent' => TRUE,
				),
				NULL,
				NULL,
				array(
					'server'     => 'otherhost',
					'port'       => 5555,
					'persistent' => TRUE,
				),
			),
			array(
				'foo',
				'bar',
				NULL,
				array(
					'server'     => 'localhost',
					'port'       => 11211,
					'persistent' => FALSE,
					'foo'        => 'bar'
				)
			),
			array(
				'server',
				NULL,
				'localhost',
				array(
					'server'     => 'localhost',
					'port'       => 11211,
					'persistent' => FALSE,
				)
			),
			array(
				NULL,
				NULL,
				array(
					'server'     => 'localhost',
					'port'       => 11211,
					'persistent' => FALSE,
				),
				array(
					'server'     => 'localhost',
					'port'       => 11211,
					'persistent' => FALSE,
				)
			)
		);
	}

	/**
	 * Tests the config method behaviour
	 * 
	 * @dataProvider provider_config
	 *
	 * @param   mixed    key value to set or get
	 * @param   mixed    value to set to key
	 * @param   mixed    expected result from [Cache::config()]
	 * @param   array    expected config within cache
	 * @return  void
	 */
	public function test_config($key, $value, $expected_result, array $expected_config)
	{
		$cache = $this->getMock('Cache', array(
				'get',
				'set',
				'delete',
				'delete_all'
			), 
			array(
				array(
					'server'     => 'localhost',
					'port'       => 11211,
					'persistent' => FALSE
				)
			)
		);

		if ($expected_result === NULL)
		{
			$expected_result = $cache;
		}

		$this->assertSame($expected_result, $cache->config($key, $value));
		$this->assertSame($expected_config, $cache->config());
	}

	/**
	 * Data provider for test_sanitize_id
	 *
	 * @return  array
	 */
	public function provider_sanitize_id()
	{
		return array(
			array(
				'foo',
				'foo'
			),
			array(
				'foo+-!@',
				'foo+-!@'
			),
			array(
				'foo/bar',
				'foo_bar',
			),
			array(
				'foo\\bar',
				'foo_bar'
			),
			array(
				'foo bar',
				'foo_bar'
			),
			array(
				'foo\\bar snafu/stfu',
				'foo_bar_snafu_stfu'
			)
		);
	}

	/**
	 * Tests the [Cache::_sanitize_id()] method works as expected.
	 * This uses some nasty reflection techniques to access a protected
	 * method.
	 * 
	 * @dataProvider provider_sanitize_id
	 *
	 * @param   string    id 
	 * @param   string    expected 
	 * @return  void
	 */
	public function test_sanitize_id($id, $expected)
	{
		$cache = $this->getMockForAbstractClass('Cache', array(array(),
			'Mock_Cache_Sanitize_Id')
		);

		$cache_reflection = new ReflectionClass($cache);
		$sanitize_id = $cache_reflection->getMethod('_sanitize_id');
		$sanitize_id->setAccessible(TRUE);

		$this->assertSame($expected, $sanitize_id->invoke($cache, $id));
	}
}