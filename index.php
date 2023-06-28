<?php

require_once 'poly/preq.php';

// --------------------------------------------------------------------------------------------------------------------

RequestMapper::setGlobalConfig(
    // we could also use a factory method here to generate a default config that should work in most cases: RequestMapperConfig::createDefaultConfig()
    new RequestMapperConfig('/pages', [ // demonstration config only
        '/admin' => new BasePathConfig('modules/regular/Admin/pages', BasePathConfig::STRIP_REQUEST_BASE),
    ], null, '.blade.php')
);

/*$c = CurrentRequest::inst(new RequestMapperConfig('/pages', [
    '/admin' => new BasePathConfig('modules/regular/Admin/pages', BasePathConfig::STRIP_REQUEST_BASE),
], null, '.blade.php'));*/

// example: use CurrentPage to access the ... current page
$page = CurrentPage::get(); // uses CurrentRequest internally
d('for current request:', $page->getUri(), $page->getName(), $page->getFilePath(),
    $page->getRequestMapper()->needsRedirect(), $page->getRequestMapper()->getRedirectUri(), $page->getRequestMapper()->pageFileExists(),
    $page->getRequestMapper(),
    //'can go forth: ' . ($page->getRequestMapper()->pageFileExists() && !$page->getRequestMapper()->needsRedirect() ? 'yes' : 'no')
);

// --------------------------------------------------------------------------------------------------------------------

// example: manual processing of a request (not using CurrentRequest)
$rm = new RequestMapper('/quxx/bla', new RequestMapperConfig('/pages', [ // demonstration config only
    '/admin' => new BasePathConfig('modules/regular/Admin/pages', BasePathConfig::STRIP_REQUEST_BASE),
], null, '.blade.php'));

$page = $rm->getPage();
d('manual', $rm->getUri(), $page->getName(), $page->getFilePath());


// --------------------------------------------------------------------------------------------------------------------

// example: afterwards update config
$cr = CurrentRequest::inst();
d($cr->mapper(), $cr->mapper()->getConfig());
$cr->mapper()->getConfig()->registerBasePath( // through the ProxyMethods trait we are also able to directly use $cr->mapper()->registerBasePath(...
    '/prefix', new BasePathConfig('pages2/foobar/pages', BasePathConfig::STRIP_REQUEST_BASE)
);
$cr->mapper()->update();
// now we should be allowed to call /prefix/bla in the browser and being returned the path to the file pages2/foobar/pages/bla.blade.php (the output of the very first $page->getName() in this script should return 404 because at that point the config we just added did not exist yet)
d($cr->mapper(), $cr->mapper()->getConfig(), $cr->mapper()->getPage()->getName());

// --------------------------------------------------------------------------------------------------------------------
