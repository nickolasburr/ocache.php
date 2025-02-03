ocache.php
==========

.. contents:: :local:

Description
-----------

Virtual object cache for PHP.

Installation
------------

.. code-block:: sh

   composer require nickolasburr/ocache.php:^1.0

Examples
--------

.. code-block:: php

   ...

   use function Ocache\cache;

   $cache = cache();
   $entry = $cache->get('example');

   if ($entry === null) {
       $entry = new \ArrayIterator(range(0, 10));
       $cache->set('example', $entry);
   }

   ...
