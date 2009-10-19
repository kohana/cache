<?php defined('SYSPATH') or die('No direct script access.');

return array
(
	'database'        => APPPATH.'cache/kohana-cache.sql3',
	'schema'          => 'CREATE TABLE caches(id VARCHAR(127) PRIMARY KEY, tags VARCHAR(255), expiration INTEGER, cache TEXT)',
	'default_expire'  => 3600,
);