<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Configuration array for Kohana Cache. The configuration is
 * separated into groups. This allows the use of multiple
 * instances of the same cache engine type- allowing two
 * unique instances of Xcache.
 * 
 * The default group is highly recommended. Without its
 * presence, a configuration group will need to be defined
 * whenever a new instance is requested.
 * 
 * Each configuration must have the following properties :-
 *  - {string}        driver          the Kohana_Cache driver to use
 * 
 * Optional setting
 *  - {int}           default_expire  the default cache lifetime
 * 
 * Each driver requires additional unique settings
 * 
 * MEMCACHE
 *  - {bool}          compression     use compression
 *  - {array}         servers         an array of available servers
 *    - {string}      host            the hostname of the memcache server
 *    - {int}         port            the port memcache is running on
 *    - {bool}        persistent      maintain a persistent connection
 * 
 *  SQLITE
 *  - {string}        database        the location of the db
 *  - {string}        schema          the initialisation schema
 * 
 *  FILE
 *  - {string}        cache_dir       the location of the cache directory
 */
return array
(
	'default'  => array
	(
		'driver'             => 'file',
	),
	// 'memcache' => array
	// (
	// 	'driver'             => 'memcache',
	// 	'default_expire'     => 3600,
	// 	'compression'        => FALSE,              // Use Zlib compression (can cause issues with integers)
	// 	'servers'            => array
	// 	(
	// 		array
	// 		(
	// 			'host'             => 'localhost',  // Memcache Server
	// 			'port'             => 11211,        // Memcache port number
	// 			'persistent'       => FALSE,        // Persistent connection
	// 		),
	// 	),
	// ),
	// 'sqlite'   => array
	// (
	// 	'driver'             => 'sqlite',
	// 	'default_expire'     => 3600,
	// 	'database'           => APPPATH.'cache/kohana-cache.sql3',
	// 	'schema'             => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
	// ),
	// 'xcache'   => array
	// (
	// 	'driver'             => 'xcache'
	// ),
);