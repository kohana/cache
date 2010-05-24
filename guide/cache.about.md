# About Kohana Cache

[Kohana_Cache] provides a common interface to a variety of caching engines. [Kohana_Cache_Tagging] is
supported where available natively to the cache system. Kohana Cache supports multiple 
instances of cache engines through a grouped singleton pattern.

## Supported cache engines

 *  APC ([Cache_Apc])
 *  eAccelerator ([Cache_Eaccelerator])
 *  File ([Cache_File])
 *  Memcached ([Cache_Memcache])
 *  Memcached-tags ([Cache_Memcachetag])
 *  SQLite ([Cache_Sqlite])
 *  Xcache ([Cache_Xcache])

## Introduction to caching

Caching should be implemented with consideration. Generally, caching the result of resources
is faster than reprocessing them. Choosing what, how and when to cache is vital. [PHP APC](http://php.net/manual/en/book.apc.php) is one of the fastest caching systems available, closely followed by [Memcached](http://memcached.org/). [SQLite](http://www.sqlite.org/) and File caching are two of the slowest cache methods, however usually faster than reprocessing
a complex set of instructions.

Caching engines that use memory are considerably faster than file based alternatives. But
memory is limited whereas disk space is plentiful. If caching large datasets, such as large database result sets, it is best to use file caching.

## Minimum requirements

 *  Kohana 3.0.4
 *  PHP 5.2.4 or greater