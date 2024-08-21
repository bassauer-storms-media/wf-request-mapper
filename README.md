# *PHP request-mapper* <small>/ auto-router / request-to-file-resolver for</small> *pretty URLs*

[![Packagist](https://img.shields.io/packagist/v/serjoscha87/php-request-mapper.svg)](https://packagist.org/packages/serjoscha87/php-request-mapper)
[![Total Downloads](https://poser.pugx.org/serjoscha87/php-request-mapper/downloads)](https://packagist.org/packages/serjoscha87/php-request-mapper)
[![php](https://img.shields.io/badge/php-5.x-red.svg)]()
[![php](https://img.shields.io/badge/php-7.x-red.svg)]()
[![php](https://img.shields.io/badge/php-8.x-green.svg)]()

> DOC MOSTLY REMOVED (because it was written for an older approach) - I'M CURRENTLY REWORKING IT <

## Implementation examples / tests:

Perhaps those examples render the complete README unnecessary.

https://github.com/serjoscha87/php-request-mapper/tree/tests

## Purpose

Ever though that it can not be that hard to reflect pretty-url requets to files on your server's file-system? Well - trying it you will quickly face edge cases that will convince you of the opposite.
This requets-mapper does the heavy lifting of reflecting pretty-url request to files on the local filesystem of the server while serving you a configurable abstraction layer.
It can handle 404 requests out of the box so you do not have to concern about it. 

The lib 
  -  Basically renders the need of a php-router for manual route-bindings obsolete
  -  Can simply be dropped in any project to easily add pretty URLs that are automatically reflected to the filesystem

I primarily wrote this for using it within a framework for more or less static websites (not apps) I wrote some time ago. But perhaps this lib can be used beyond its actual purpose.

## Installation

``composer require serjoscha87/php-request-mapper``

### .htaccess

**For Apache Webservers:**

_the all known 'wordpress' rewrite rule which rewrites all requests to things that do not physically exist to index.php:_

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

**Nginx rewrites requests to the index.php by default - no extra rules needed.**

## Requirements

  - PHP8+ (because the lib makes use of named arguments and array unpacking)

## Simple usage Example

**filesystem:**
```
index.php
pages
- test.php
- home.php
- 404.php
- foobar (dir)
  - foobar.php
  - abc.php
  - detail.php
```

(of course the level of dir-nesting is not limited by the request-mapper)


### Examples for better understanding what the request mapper does for you

considering the filesys structure given above (see "Simple usage Example"):

  - Request to: ``/test`` -> will deliver the content of ``pages/test.php``
  - Request to: ``/quxx`` -> will deliver the content of ``pages/404.php`` (because there is no quxx.php in the pages dir)
  - Request to: ``/`` -> will deliver the content of ``pages/home.php``
  - Request to: ``/home`` -> the requestmapper will tell you that a redirect to ``/`` is required (through ``CurrentRequest::needsRedirect()`` - you can use ``CurrentRequest::getRedirectUri()`` to get the redirect target)
  - Request to: ``/foobar/foobar`` -> the requestmapper will tell you that a redirect to ``/foobar`` is required (see above)
  - Request to: ``/foobar`` -> will deliver the content of ``pages/foobar/foobar.php``
  - Request to: ``/foobar/detail/something`` -> will deliver the content of ``pages/foobar/detail.php`` and will pass everything after ``/detail`` to the php file
  - Request to: ``/foobar/abc`` -> like seen before ... nothing special. Will deliver the content ``pages/foobar/abc.php``

## misc

This lib also works perfectly fine with great template engines / implementations like [BladeOne](https://github.com/serjoscha87/php-request-mapper)
