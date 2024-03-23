vfscache
========

.. contents:: :local:

Description
-----------

VFS object cache for PHP.

Installation
------------

.. code-block:: sh

   composer require nickolasburr/vfscache:^1.0

Examples
--------

.. code-block:: php

   ...

   use function VfsCache\cache;

   $cache = cache();
   $entry = $cache->get('example');

   if ($entry === null) {
       $entry = new \ArrayIterator(range(0, 10));
       $cache->set('example', $entry);
   }

   ...
