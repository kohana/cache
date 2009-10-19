<?php defined('SYSPATH') or die('No direct script access.');

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
);