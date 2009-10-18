<?php defined('SYSPATH') or die('No direct script access.');
/**
 * NOTICE
 * 
 * Memcache does not support caching. This module provides limited
 * tagging support for Memcache. The tagging system will fail if the
 * tag index exceeds 1MB in size, due to the limitations to Memcache.
 * 
 * If the index exceeds 1MB in size, then another caching engine will
 * need to be used. It should be noted that indexes larger than 1MB
 * are probably too big, implying the application is not using
 * caching correctly.
 * 
 * To avoid these issues, turn off tagging support by setting the
 * 'tagging' value to FALSE
 *
 */
return array
(
	'servers'       => array
	(
		array
		(
			'host'          => 'localhost',  // Memcache Server
			'port'          => 11211,        // Memcache port number
			'persistent'    => FALSE,        // Persistent connection
		),
	),
	'compression'   => FALSE,        // Use Zlib compression (can cause issues with integers)
	'tagging'       => TRUE,         // Allow tagging
);