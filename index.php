<?php

/*
 * before testing run
 * > composer require serjoscha87/php-request-mapper
 */
require_once 'vendor/autoload.php';

require_once '../sage.phar'; // for d() debugging function - https://github.com/php-sage/sage

if(PHP_MAJOR_VERSION === 8) {
    function dd(...$v) {
        d(...$v);
        exit;
    }
}

use serjoscha87\phpRequestMapper\{IPage, Mapping, Page, RequestMapper, SolidUri, DetailPage, Default404Page};

/*
 * 1. priority test
 */
$priorityTest = function () {

    $baseMapping = Mapping::createFor('foobar')
        ->setDefaultPagePath('test')
        ->setPageBasePath('pages-prio-test');

    $rm = new RequestMapper([
        Mapping::createDefault(),
        (clone $baseMapping)
            ->onMatch(function(Mapping $mapping, RequestMapper $mapper) {
                define('MATCHED_REQUEST_BASE', 'foo');
            })
            ->setPriority(20), // Note that higher priority values will be executed first
        (clone $baseMapping)
            ->onMatch(function(Mapping $mapping, RequestMapper $mapper) {
                define('MATCHED_REQUEST_BASE', 'bar');
            })
            ->setPriority(30) // Note that higher priority values will be executed first
    ]);

    $rm->setRespectPriorities(true);

    $rm->run();

    $rm->handle(function(IPage $page) {
        require_once $page->getFilePath();
    });

    exit;
};
//$priorityTest();

/*
 * 2. regular test
 */

?>
<a href="/index.php">index.php => redirect to /</a> <br>
<a href="/home">/home => redirect to / (while 'home' is the default page)</a> <br>
<a href="/test">/test => delivers the content of 'pages/test.php'</a> <br>
<a href="/foobar">/foobar => delivers content of 'pages/foobar/foobar.php'</a> <br>
<a href="/foobar/foobar">/foobar/foobar => redirect to /foobar</a> <br>
<a href="/foobar/test">/foobar/test => 404 (page does simply not exist)</a> <br>
<a href="/foobar//////bla">/foobar//////bla => redirect to /foobar/bla and then delivers the content of 'pages/foobar/bla.php'</a> <br>
<a href="/foobar/detail/test-test?some=query">/foobar/detail/test-test?some=query => dynamic detail page</a> <br>
<a href="/foobar/detail/test-test/this-is-also-a-param?some=query">/foobar/detail/test-test/this-is-also-a-param?some=query => dynamic detail page</a> <br>
<a href="/baz/detail/baz">/baz/detail/baz => 404 because there is no detail page file for this request</a> <br>
<a href="/foobar/sub">/foobar/sub => deliver the content of 'pages/foobar/sub/sub.php'</a> <br>
<a href="/foobar/sub/sub">/foobar/sub/sub => redirects to '/foobar/sub'</a> <br>
<a href="/foobar/sub/another">/foobar/sub/another => delivers the content of '/pages/foobar/sub/another.php'</a> <br>
<a href="/admin">/admin => delivers default page 'dashboard'</a> <br>
<a href="/admin/dashboard">/admin/dashboard => redirects to /admin</a> <br>
<a href="/admin/news">/admin/news => shows content of 'news.php'</a> <br>
<a href="/en">/en => delivers the content of 'pages/home.php' while passing the information that this page should be rendered in english</a> <br>
<a href="/en/test">/en/test => delivers the content of 'pages/test.php' while passing...</a> <br>
<a href="/fr/test">/fr/test => same for lang fr</a> <br>
<a href="/en/test-test">/en/test-test => 404 while passing the language information to the page</a> <br>
<a href="/us">/us => same as for /en but implemented as static lang</a> <br>
<a href="/us/test">/us/test => same as /en/test (but impl. is static)</a> <br>
<a href="/us?skip">/us?skip => uses the second mapping def. for 'us' and just generates the same output as /us - this is just form demonstrating match interventions through impl.</a> <br>
<a href="/quxxing">/quxxing => delivers the content of 'pages2/home.php'</a> <br>
<a href="/quxxing/test">/quxxing/test => delivers the content of 'pages2/test.php'</a> <br>
<a href="/quxxing/does-not-exist">/quxxing/does-not-exist => 404 (because simple does not exist)</a> <br>
<a href="/bazpartialwombat">/bazpartialwombat => will deliver the content of 'pages/home.php' (because there is no dedicated further config)</a> <br>
<a href="/bazpartialwombat/test">/bazpartialwombat/test => will deliver the content of 'pages/test.php' (because there is no dedicated further config)</a> <br>
<a href="/partialwombat">/partialwombat => same as /bazpartialwombat</a> <br>
<a href="/bazpartial">/bazpartial => same as /bazpartialwombat</a> <br>
<a href="/regextest">/regextest => delivers the content of 'pages/home.php'</a> <br>
<a href="/regextest/test">/regextest/test => delivers the content of 'pages/test.php'</a> <br>
<hr>
<?php

// fully optional, but allows to define custom page class logics
class MyCustomPage extends Page {

    public function getSliderImages () {
        $imgs = glob($this->getBasePath() . '/images/slider/*');
        return empty($imgs) ? ['/imgs/default-slider-imgs/slider01.jpg'] : $imgs;
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

            // demo impl. of another 404 page for detail pages that do not exists
            if($page instanceof Default404Page && $mapper->isDetailPageRequest() && isset($_GET['test-alter-404']))
                $page->setFilePath('pages/404-detail.php');
        })
        ->onMatch(function(Mapping $mapping, RequestMapper $mapper) {
            define('LANG', 'de');
            d('matched default mapping');
        })
        ->onTap(function(Mapping $mapping, RequestMapper $mapper) {})
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
    })
    /*->onPageInstantiationComplete(function(IPage $page, Mapping $mapping, RequestMapper $mapper) {
        echo 'multi language page instantiation complete';
    })*/,


    // test for static language mapping (/us/<page>)
    Mapping::createFor('us')
        ->setStrip(Mapping::STRIP_REQUEST_MATCHER_STRING)
        //->setPageBasePath('pages')
        //->setDefaultPagePath('home')
        ->onMatch(function() {
            if(isset($_GET['skip']))
                return false;
            define('LANG', 'us');
        }),
    Mapping::createFor('us')
        ->setStrip(Mapping::STRIP_REQUEST_MATCHER_STRING)
        ->onMatch(function() {
            define('LANG', 'us');
            d('us lang fallback (matches if the first us mapping is skipped as it matches)');
        }),


    Mapping::createFor('admin')
        ->setStrip(Mapping::STRIP_REQUEST_MATCHER_STRING)
        ->setPageBasePath('admin-pages')
        ->setDefaultPagePath('dashboard'),


    Mapping::createFor(function(Mapping $mapping, SolidUri $requestUri, RequestMapper $rm) : bool {
        //$mapping->setStrip('quxxing');
        return str_contains($requestUri, '/quxxing');
    })
    ->onMatch(function(Mapping $mapping, RequestMapper $mapper) {
        //$mapping->setStrip('quxxing');
    })
    ->setStrip('quxxing') // we can set the strip prop directly in the createFor binding, or in the onMatch callback, or via invocation on the mapping factory chain (setStrip) where the last option may be the most readable
    ->setPageBasePath('pages2'),


    Mapping::createFor('/regextest/?', Mapping::MATCHING_METHOD_REGEX),
        //->setStrip('regextest')


    Mapping::createFor('partial', Mapping::MATCHING_METHOD_STR_CONTAINS)
        ->onMatch(function(Mapping $mapping, RequestMapper $rm) {
            //echo 'match on "partial"';
            //d($mapping, $rm);
        }),
        //->setStrip('/bazpartial'),
        /*->setStrip(function(string $destFile, Mapping $mapping, RequestMapper $rm) : string|int|null {
            //dd($destFile);
            //return '/bazpartial';
            //return 'bazpartial';
            $matches = [];
            preg_match('~\/(?<partialMatch>\w*partial\w*)\/~', $destFile, $matches);
            return $matches['partialMatch'] ?? null;
            //dd($matches);
            //return 'test';
        }),*/


    //Mapping::createFor('ajax')

];

$rm = new RequestMapper($demoMappings, /*true*//*< run immediately */);

// here some module logic could be implemented which required to do something with the RM
RequestMapper::$primaryInstance->beforeRun(function() {
    error_log('before run!');
});

$mainTest = function() use ($rm) {
    $rm->run();

    $rm->handle(function(IPage $page) use ($rm) {
        require_once $page->getFilePath();
    });
};
$mainTest();

/*
 * BladeOne example
 */

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

$bladeTest = function() use ($rm, $blade) {
    $rm->handle(function(BladePage $page) use ($blade) {
        echo $blade->run($page->getFilePath());
    });
};
//$bladeTest();
