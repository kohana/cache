<?php defined('SYSPATH') or die('No direct script access.');

return array
(
	'type'               => 'apc',
	'default-expire'     => 3600,
	'memcache'           => array
	(
		'servers'            => array
		(
			array
			(
				'host'             => 'localhost',  // Memcache Server
				'port'             => 11211,        // Memcache port number
				'persistent'       => FALSE,        // Persistent connection
			),
		),
		'compression'         => FALSE,        // Use Zlib compression (can cause issues with integers)
	),
	'sqlite'             => array
	(
		'database'        => APPPATH.'cache/kohana-cache.sql3',
		'schema'          => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
	),
);