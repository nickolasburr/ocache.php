fcache
======

.. contents:: :local:

Description
-----------

Simple object cache for PHP.

Installation
------------

.. code-block:: sh

   composer require nickolasburr/fcache:^1.0

Examples
--------

.. code-block:: php

   ...

   use function Fcache\cache;

   $cache = cache();
   $entry = $cache->get('example');

   if ($entry === null) {
       $cache->set('example', new \ArrayIterator([]));
   }

   ...
