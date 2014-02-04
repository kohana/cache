<?php defined('SYSPATH') or die('No direct script access.');
return array
(
/*	'memcache' => array(
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
		'instant_death'      => TRUE,               // Take server offline immediately on first fail (no retry)
	),
    'memcached' => array(
   		'driver'             => 'memcached',
        'persistent'         => FALSE,                                                  // Persistent connection
   		'options'            => array(
            Memcached::OPT_SERIALIZER           => Memcached::SERIALIZER_IGBINARY,      // Use Memcached::SERIALIZER_PHP, Memcached::SERIALIZER_IGBINARY or Memcached::SERIALIZER_JSON serializer
            Memcached::OPT_COMPRESSION          => FALSE,                               // Enables or disables payload compression.
            Memcached::OPT_LIBKETAMA_COMPATIBLE => TRUE,                                // Enables or disables compatibility with libketama-like behavior.
            Memcached::OPT_HASH                 => Memcached::HASH_MD5,                 // Memcached::HASH_MD5, Memcached::HASH_CRC
            Memcached::OPT_DISTRIBUTION         =>  Memcached::DISTRIBUTION_CONSISTENT, // Use Memcached::DISTRIBUTION_CONSISTENT only if you have LIBKETAMA, otherwise Memcached::DISTRIBUTION_MODULA
            Memcached::OPT_NO_BLOCK             => TRUE,                                // Enables or disables asynchronous I/O. This is the fastest transport available for storage functions.
            Memcached::OPT_CONNECT_TIMEOUT      => 3000,                                // Connection timeout
            Memcached::HAVE_IGBINARY            => TRUE,                                // Indicates whether igbinary serializer support is available.
            Memcached::OPT_PREFIX_KEY           => '_memcd',                            // Use prefix with keys. Use when multiple kohana framework is being used on one server
        ),
        'servers'            => array(
   			array(
   				'host'             => 'localhost',                                      // Memcached Server
   				'port'             => 11211,                                            // Memcached port number
   				'weight'           => 1,
   			),
   		),
   	),
	'memcachetag' => array(
		'driver'             => 'memcachetag',
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
	),
	'apc'      => array(
		'driver'             => 'apc',
		'default_expire'     => 3600,
	),
	'wincache' => array(
		'driver'             => 'wincache',
		'default_expire'     => 3600,
	),
	'sqlite'   => array(
		'driver'             => 'sqlite',
		'default_expire'     => 3600,
		'database'           => APPPATH.'cache/kohana-cache.sql3',
		'schema'             => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
	),
	'eaccelerator'           => array(
		'driver'             => 'eaccelerator',
	),
	'xcache'   => array(
		'driver'             => 'xcache',
		'default_expire'     => 3600,
	),
	'file'    => array(
		'driver'             => 'file',
		'cache_dir'          => APPPATH.'cache',
		'default_expire'     => 3600,
		'ignore_on_delete'   => array(
			'.gitignore',
			'.git',
			'.svn'
		)
	)
*/
);
