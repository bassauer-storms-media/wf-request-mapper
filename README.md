# *PHP request-mapper* <small>/ auto-router / request-to-file-resolver for</small> *pretty URLs*

[![Packagist](https://img.shields.io/packagist/v/serjoscha87/php-request-mapper.svg)](https://packagist.org/packages/serjoscha87/php-request-mapper)
[![Total Downloads](https://poser.pugx.org/serjoscha87/php-request-mapper/downloads)](https://packagist.org/packages/serjoscha87/php-request-mapper)
[![php](https://img.shields.io/badge/php-5.x-red.svg)]()
[![php](https://img.shields.io/badge/php-7.x-red.svg)]()
[![php](https://img.shields.io/badge/php-8.x-green.svg)]()

> README STILL ON THE WAY TO COMPLETENESS <

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

**index.php:**

```php
use serjoscha87\phpRequestMapper\_\CurrentRequest;use serjoscha87\phpRequestMapper\CurrentPage;

// if you need the mapper instance for the current request
// $rm = CurrentRequest::inst()->mapper(); 

// get all information on the page that is currently mapped by the concrete request
$currentPage = CurrentPage::get();

var_dump($currentPage->getName());
var_dump($currentPage->is404());
var_dump($currentPage->getFilePath());
// ...

// send the page out for rendering
if(CurrentRequest::needsRedirect()) {
  // ... do the header redirect here
  header('HTTP/1.0 404 Not Found');
  header('Location: ' . CurrentRequest::getRedirectUri());
  exit;
}

if(!CurrentRequest::isReal404())
    require_once $currentPage->getFilePath(); // this will automatically send out the content of the 404 page if the page requested and mapped does not exist - otherwise it will deliver the content of the files the mapper mapped
    
```

**Major alternative which does all the checking & redirecting stuff for you:**
```php
CurrentRequest::handle(function(IPage $page) {
    require_once $page->getFilePath();
    // or for example if your are using some cool lib like BladeOne (and assuming you configured it) 
    // echo $blade->run($page->getFilePath());
});
```

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

### self instancing:

(creating an instance to use your self rather than using the factory classes CurrentRequest / CurrentPage)

```php
$rm = new RequestMapper('/my-emulation-url', /*<*/CONFIG/*> (optional when a global config exists)*/);
```

## configuration

```php
RequestMapper::setGlobalConfig(new RequestMapperConfig(
    new BasePathConfig(
        'pages',
        defaultPage: '/home',
        pageFileExtension: 'php'
        // pageClass: MyCustomPage::class // < of course this class must exist (and should implement IPage) 
        // detailPageIdentifier: 'item'
    ),
    //defaultDetailPageIdentifier: 'detail'
    //defaultDefaultPage : 'test',
    //defaultPageFileExtension : 'php'
    [
        '/admin' => new BasePathConfig('admin-pages', BasePathConfig::STRIP_REQUEST_BASE, 'dashboard', 'php'),
    ]
));
```

of course you can also pass a config directly to the constructor of the actual RequestMapper class which will override the global config for this instance:

```php
$myCustomRequestMapper = new RequestMapper('/my-emulation-url', new RequestMapperConfig(
    new BasePathConfig(/*...*/)
));
```

### Mapping Priority 

TODO 

... higher values mean higher priority ...

### Defaults and fallbacks

Those default properties can be passed to the **RequestMapperConfig** constructor:

```php
?string $defaultDefaultPage = null
?string $defaultPageFileExtension = null
?string $defaultDetailPageIdentifier = null
```

those are used if a concrete BasePathConfig instance does not provide a value for them.

Each BasePathConfig can have its own values for those properties.
Furthermore the following properties can be passed to the constructor of the **BasePathConfig** class:

```php
string $basePath, // < filesystem path - typically 'pages'. This is the dir where your page files are located
null|string|int/*<BasePathConfig::STRIP_REQUEST_BASE>*/ $strip = null,
/*O*/?string $defaultPage = null, // the default page to be delivered if the request is just a root request - typically this will be 'home' (which will be reflected to 'home.php' in the pages dir)
/*O*/?string $pageFileExtension = null, // if set: overrides the default page file extension set to the RequestMapperConfig
?string $pageClass = null, // the full qualified class name of the class that represents a found page - should implement IPage
?string $fourOFourPageClass = null, // same as above but for 404 pages
?string $detailPageClass = null, // same as above but for detail pages
/*O*/?string $detailPageIdentifier = null // the name of the files that will be used as detail pages - typically 'detail' (which will be reflected to 'detail.php' in the dir the request is mapped to)
```
where ```/*O*/``` indicates that the value overrides RequestMapperConfig props if set

## RequestMapper::isReal404()

...
important thing...
Imagine you let the request mapper answer all 404 requests with a cool html error page. 
Now imagine you are having a gallery in your websites which has some wrong path references and causes 20 of the images to run into a 404.
Without making your logic check if the request is a 'real404' every of those asset requests will return a pretty 404 page markup. 
You just don't want this if you are using a framework like me - because this means your framework will not only be booted for handling the actual website request but also for every single 404 asset request which causes massive server load for noting.
This is why the request mapper has this method. 
Make sure to use it before sending out the page html:

```
if(!CurrentRequest::isReal404()) {
     // your logic for outputting the page content here
}
```
...

## misc

This lib also works perfectly fine with great template engines / implementations like [BladeOne](https://github.com/serjoscha87/php-request-mapper)
