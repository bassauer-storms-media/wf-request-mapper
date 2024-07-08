<?php

require_once 'vendor/autoload.php';

//require_once '../kint-new/kint.phar';
require_once '../kint-new/SAGE/sage.phar';

?>
    <a href="/index.php">index.php => redirect to /</a> <br>
    <a href="/home">/home => redirect to / (while 'home' is the default page)</a> <br>
    <a href="/foobar">/foobar => delivers content of 'foobar.php'</a> <br>
    <a href="/foobar/foobar">/foobar/foobar => redirect to /foobar</a> <br>
    <a href="/foobar//////bla">/foobar//////bla => redirect to /foobar/bla</a> <br>
    <a href="/foobar/detail/test-test?some=query">/foobar/detail/test-test?some=query => dynamic detail page</a> <br>
    <a href="/admin">/admin => delivers default page 'dashboard'</a> <br>
    <a href="/admin/dashboard">/admin/dashboard => redirects to /admin</a> <br>
    <a href="/admin/news">/admin/news => shows content of 'news.php'</a> <br>
    <hr>
<?php

/*if(PHP_MAJOR_VERSION === 8) {
    function dd(...$v) {
        d(...$v);
        exit;
    }
}*/

use serjoscha87\phpRequestMapper\{IPage, Mapping, Page, RequestMapper, SolidUri, DetailPage, Default404Page};

// just a test...
//RequestMapper::$DETERMINE_INSTANCED_BY = true;

// fully optional, but allows to define custom page class logics
class MyCustomPage extends Page {

    public function getSliderImages () {
        return glob($this->getBasePath() . '/images/slider/*');
    }

    public function getRandomNumber () {
        return rand(1, 100);
    }

}

$demoMappings = [
    Mapping::createDefault()
        //->setPageBasePath('pages')
        //->setDefaultPagePath('home')
        ->setDefaultPagePath('home')
        ->onPageInstantiationComplete(function(IPage &$page, Mapping $mapping, RequestMapper $mapper) {
            // test override the configured detail page class in some special case
            if($page instanceof DetailPage && isset($_GET['fake-404']))
                $page = new Default404Page($mapper);
        })
        ->onMatch(function(Mapping $mapping, RequestMapper $mapper) {
            define('LANG', 'de');
        })
        ->onTap(function(RequestMapper $mapper) {})
        ->set200PageClass(MyCustomPage::class),

    // just a super simple mapping config
    /*Mapping::createDefault()
        ->set200PageClass(MyCustomPage::class),*/

    // adding dynamic language mapping example
    Mapping::createFor(function(Mapping &$mapping, SolidUri $requestUri) : bool {
        $lang = explode('/', $requestUri->getUri())[1] ?? null;

        // variant 1
        $variant1 = function() use (&$mapping, $lang) {
            // instance a complete new mapping object
            $mapping = Mapping::createFor($lang)
                ->setStrip(Mapping::STRIP_REQUEST_BASE)
                ->onMatch(function() use ($lang) {
                    define('LANG', $lang);
                });
        };
        // -- variant 1

        // variant 2
        $variant2 = function() use (&$mapping, $lang) {
            // override the current mapping object settings
            $mapping
                ->setStrip($lang)
                ->onMatch(function() use ($lang) {
                    define('LANG', $lang);
                });
        };
        // -- variant 2

        //$variant1();
        $variant2();

        return in_array($lang, ['de', 'en', 'fr', 'es']);
    }),

    // test for static language mapping (/us/<page>)
    Mapping::createFor('us')
        ->setStrip(Mapping::STRIP_REQUEST_BASE)
        //->setPageBasePath('pages')
        //->setDefaultPagePath('home')
        ->onMatch(function(){
            define('LANG', 'us');
        }),

    Mapping::createFor('admin')
        ->setStrip(Mapping::STRIP_REQUEST_BASE)
        ->setPageBasePath('admin-pages')
        ->setDefaultPagePath('dashboard'),

    // we can set the strip prop directly in the createFor binding, or in the onMatch callback, or via invocation on the mapping factory chain (setStrip) where the last option may be the most readable
    Mapping::createFor(function(Mapping $mapping, SolidUri $requestUri, RequestMapper $rm) : bool {
        //$mapping->setStrip('quxxing');
        return str_contains($requestUri, '/quxxing');
    })
    ->onMatch(function(Mapping $mapping, RequestMapper $mapper) {
        //$mapping->setStrip('quxxing');
    })
    ->setStrip('quxxing')
    ->setPageBasePath('pages2')

];

$rm = new RequestMapper($demoMappings, /*true*//*< run immediately */);

// here some module logic could be implemented which required to do something with the RM
RequestMapper::$primaryInstance->beforeRun(function() {
    error_log('before run!');
});

$rm->run();

$rm->handle(function(IPage $page) use ($rm) {
    require_once $page->getFilePath();
});

/*if(RequestMapper::$DETERMINE_INSTANCED_BY)
    d($rm->getInstancedBy());*/

echo str_repeat('<hr>', 5);

/*
 * BladeOne example
 */

return;

class BladePage extends Page {

    private function cleanUri($uri2clean) {
        return implode('/', array_filter(preg_split('/[\\|\/]/', $uri2clean)));
    }

    public function getFilePath() : ?string {
        return str_replace([$this->getRequestMapper()->getPageFileExtension(), '/'], ['','.'], $this->filePath);
    }
}

$demoMappingsBlade = [
    Mapping::createDefault()
        ->setPageFileExtension('.blade.php')
        ->setPageBasePath('pages-blade')
        ->set200PageClass(BladePage::class)
];

$rm = new RequestMapper($demoMappingsBlade, true);

use eftec\bladeone\BladeOne;
$views = __DIR__ . '/.';
$cache = __DIR__ . '/cache';
$blade = new BladeOne($views, $cache, BladeOne::MODE_DEBUG);

$rm->handle(function(BladePage $page) use ($blade) {
    echo $blade->run($page->getFilePath());
});
