# PHP request-mapper / auto-router / request-to-file-resolver

> README STILL ON THE WAY TO COMPLETENESS <

## Purpose

Ever though that it can not that be hard to map smart-url requets to file on your file-system? Well. You quickly approch edge cases that will convince you from the opposite.

This requets-mapper does the heavy lifting of mapping request to files on the local filesystem of the server while serving a configurable abstraction layer.

## Installation

composer require serjoscha87/php-request-mapper

## Simple Example

**filesystem:**
```
index.php
pages
- test.php
- home.php
- 404.php
foobar
- foobar.php
- abc.php
- detail.php
```

**index.php:**

```
use serjoscha87\phpRequestMapper\CurrentRequest;
use serjoscha87\phpRequestMapper\CurrentPage;

// if you need the mapper instance for the current request
// $rm = CurrentRequest::inst()->mapper(); 

// get all information on the page that is currently mapped by the concrete request
$currentPage = CurrentPage::get();

var_dump($currentPage->getName());
var_dump($currentPage->is404());
var_dump($currentPage->getFilePath());
// ...

// send the page out for rendering
require_once $currentPage->getFilePath(); // this will automatically send out the content of the 404 page if the page requested and mapped does not exist
```

### Examples for better understanding what its good for

considering the filesys structure given above.

  - Request to: /test -> will deliver the content of 'pages/test.php'
  - Request to: /quxx -> will deliver the content of 'pages/404.php' (because there is no quxx.php in the pages dir)
  - Request to: / -> will deliver the content of 'pages/home.php'
  - Request to: /home -> the requestmapper will tell you that a redirect to '/' is required (through CurrentRequest::needsRedirect() - you can use CurrentRequest::getRedirectUri() to get the redirect target)
  - Request to: /foobar/foobar -> the requestmapper will tell you that a redirect to '/foobar' is required (see above)
  - Request to: /foobar -> will deliver the content of 'pages/foobar/foobar.php'
  - Request to: /foobar/detail/something -> will deliver the content of 'pages/foobar/detail.php' and will pass everything after /detail to the php file
  - Request to: /foobar/abc -> like seen before ... nothing special. Will deliver the content 'pages/foobar/abc'

### self instancing:

```
$rm = new RequestMapper('/my-emulation-url');
```

... up to come

## configuration

up to come...

but generally pages dir, extensions, prefixes and base paths are configurable
