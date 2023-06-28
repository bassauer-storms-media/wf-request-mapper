<style>
    .xdebug-var-dump {
        display: inline;
    }
    .xdebug-var-dump > small, .xdebug-var-dump > i {
        display: none;
    }
    .xdebug-var-dump > font[color="#cc0000"] {
        color: black;
    }

    section:nth-child(odd) {
        background-color: #ededed;
        padding: 40px 0 40px 20px;
        margin-top: 40px;
    }
    section:nth-child(even) {
        margin-top: 40px;
        padding: 0 20px;
    }

    h1 {
        margin: 0;
    }
    h1 + div {
        margin-bottom: 40px;
    }
</style>
<?php

require_once 'preq.php';

//dd(__DIR__ . '/pages', is_dir(__DIR__ . '/pages'));

$disable_all = true;

$testBasePaths = [ // demonstration config only
    '/admin' => new BasePathConfig(__DIR__ . '/admin-pages', BasePathConfig::STRIP_REQUEST_BASE, (new class extends Default404Page {
        public function getFilePath() : string {
            return '/admin-pages/404.blade.php';
        }
    })),
]; // perhaps creata a BasePathCollection class? Just for better understanding of the code ...

RequestMapper::setGlobalConfig(
    //new RequestMapperConfig(__DIR__  . '/pages', $testBasePaths, null, '.blade.php') // TODO IMPORTANT!!! TEST IF EVERYTHING HERE ALSO WORKS WITHOUT A ABSOLUT PATH (so without __DIR__)
    new RequestMapperConfig(new BasePathConfig(__DIR__  . '/pages'), $testBasePaths, '.blade.php')
);

echo '<hr/>';
echo 'page base: <b>' . (__DIR__ .'/pages').'</b>';
echo '<hr style="margin-bottom: 40px"/>';

/*
 * Redirection tests
 */
echo '<section>';

echo '<h1>redirect tests</h1>';
echo '<div>Remember for the following tests that page files MUST exist in order for the request mapper to tell that we need a redirect, because if the page file does not exist we don\'t need a redirect</div>';

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req='/quxx/does/not/exist');
    $expecting = null;
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ' => expecting NO!)</small><br/>';
    echo 'expecting $..->needsRedirect() to be ';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>';
    echo '<small style="opacity: 0.2">' . str_repeat('#', 80) . '</small>';
    echo '<br/>';
    echo '<i>This page file does not exist on the disc, so the page name should be 404</i> <br/>';
    echo 'result: ';
    var_dump($rm->getPage()->getName());
    echo getAssertionResult($rm->getPage()->getName() === '404');
    echo '<br/>';
    echo '<small style="opacity: 0.2">' . str_repeat('#', 80) . '</small>';
    // TODO hier weiter!!!
    // TODO hier noch checken ob die 404 page so ist wie erwartet (jede basepath config kann jetzt selber definieren welche 404 seite bei not found ausgeliefert werden soll - testen ob das hier mit der default funktioniert)

    echo '<hr/>';
})(true && !$disable_all);


(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/exists/');
    $expecting = true;
    $expecting2 = '/this/exists';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>expecting $..->getRedirectUri() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getRedirectUri());
    // assertion
    echo getAssertionResult($rm->getRedirectUri() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/exists///');
    $expecting = true;
    $expecting2 = '/this/exists';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>expecting $..->getRedirectUri() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getRedirectUri());
    // assertion
    echo getAssertionResult($rm->getRedirectUri() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/////this////exists///');
    $expecting = true;
    $expecting2 = '/this/exists';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>expecting $..->getRedirectUri() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getRedirectUri());
    // assertion
    echo getAssertionResult($rm->getRedirectUri() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/exists');
    $expecting = false;
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this');
    $expecting = false;
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);


(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/this');
    $expecting = true;
    $expecting2 = '/this';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>expecting $..->getRedirectUri() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getRedirectUri());
    // assertion
    echo getAssertionResult($rm->getRedirectUri() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);


(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/detail/test-query');
    $expecting = false;
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/detail/test-query/');
    $expecting = true;
    $expecting2 = '/this/detail/test-query';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>expecting $..->getRedirectUri() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getRedirectUri());
    // assertion
    echo getAssertionResult($rm->getRedirectUri() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);


(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/exists?foo=bar');
    $expecting = false;
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this///exists?foo=bar');
    $expecting = true;
    $expecting2 = '/this/exists?foo=bar';
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion
    echo '<br/>expecting $..->getRedirectUri() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getRedirectUri());
    // assertion
    echo getAssertionResult($rm->getRedirectUri() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);


/*
 * This is a case I am not really sure about.
 * A real request should never miss the guiding slash, so it's difficult to say what the expected behaviour should be if there would be an input without a guiding slash.
 */
(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = 'this/exists');
    $expecting = true;
    echo '<small>(page exists: ' . ($rm->pageFileExists() ? '<b style="color: green">yes</b>' : '<b style="color: red">no</b>') . ')</small><br/>';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->needsRedirect() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->needsRedirect());
    // assertion
    echo getAssertionResult($rm->needsRedirect() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

echo '</section>';

/*
 * Redirection tests
 */
echo '<section>';
echo '<h1>page file resolve tests</h1>';
echo '<div>These tests shall prove that the mapper is able to return the right file on the FS for any request</div>';

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/this/exists');
    $expecting = RequestMapper::getGlobalConfig()->getDefaultBasePath() .  $req . RequestMapper::getGlobalConfig()->getPageFileExtension();
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->getPage()->getFilePath() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->getPage()->getFilePath());
    // assertion
    echo getAssertionResult($rm->getPage()->getFilePath() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/quxx/does/not/exist');
    $expecting = RequestMapper::getGlobalConfig()->getDefaultBasePath() .  '/404' . RequestMapper::getGlobalConfig()->getPageFileExtension();
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->getPage()->getFilePath() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->getPage()->getFilePath());
    // assertion
    echo getAssertionResult($rm->getPage()->getFilePath() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

echo '</section>';

/*
 * BasePath*s* test
 */
echo '<section>';
echo '<h1>BasePath\'s tests</h1>';
echo '<div></div>';

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/admin/this/exists');
    $expecting = true;
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->pageFileExists() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->pageFileExists());
    // assertion
    echo getAssertionResult($rm->pageFileExists() === $expecting);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

(function ($run=true) {
    if(!$run)
        return;

    $rm = new RequestMapper($req = '/admin/this/exists-not');
    $expecting = false;
    $expecting2 = '/admin-pages/404.blade.php';
    echo 'emulating request: <b>'.$req.'</b><br/>';
    echo 'expecting $..->pageFileExists() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->pageFileExists());
    // assertion
    echo getAssertionResult($rm->pageFileExists() === $expecting);
    // -- assertion
    echo '<br/>';
    echo 'expecting $..->getPage()->getFilePath() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getPage()->getFilePath());
    // assertion
    echo getAssertionResult($rm->getPage()->getFilePath() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

echo '</section>';

/*
 * 'Current'-Helper classes test
 */
echo '<section>';
echo '<h1>"Current"-Helper tests</h1>';
echo '<div>Note that these tests may suddenly fail if you move the testing script / script content somewhere else then the "tests" dir (because the dir name is hardcoded in the tests assertion)</div>';

(function ($run=true) {
    if(!$run)
        return;

    echo '<b>CurrentRequest Helper:</b><br/>';

    $rm = CurrentRequest::inst()->mapper();
    $expecting = '/tests';
    $expecting2 = false;
    echo '(real) request: <b>'.$rm->getUri().'</b><br/>';
    echo 'expecting $..->getUri() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->getUri());
    // assertion
    echo getAssertionResult($rm->getUri() === $expecting);
    // -- assertion
    echo '<br/>';
    echo 'expecting $..->pageFileExists() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->pageFileExists());
    // assertion
    echo getAssertionResult($rm->pageFileExists() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true);

(function ($run=true) {
    if(!$run)
        return;

    echo '<b>CurrentPage Helper:</b><br/>';

    /*
     * TODO hier weiter machen ... habe ich mich noch nicht wirklich mit auseinander gesetzt... mal genau checken
     */

    //$rm = CurrentPage::getRequestMapper();
    //echo '(real) request: <b>'.$rm->getUri().'</b><br/>'; // << wie erwartet korrekt "/tests"
    //echo '<hr><hr><hr><hr>';

    // so funktioniert es auch, was richtig ist:
    //$rm = new RequestMapper($req = '/admin/this/exists');
    //CurrentRequest::inst()->override($rm);

    $page = CurrentPage::get(); /* @var $page IPage | Page|Default404Page|DetailPage */
    var_dump(get_class($page));
    $expecting = '/tests';
    $expecting2 = false;
    // neuste erkenntnis:
    // CurrentPage::get() macht intern ein CurrentRequest::inst() was eben wie der name schon sagt den aktuellen request verwendet, welche "/tests" ist und wozu es keine page gibt...
    // daher gibt getUri() "/404" zurück was soweit eigentlich nicht mal wirklich falsch ist.
    // die essentielle frage ist also was getUri() zurück geben sollte wenn es keine page gibt.
    // Vermutlich wäre es sinnvoll in diesem fall null zurück zu geben, was aber mit der aktuellen art und weise wie die klassen gebaut sind nicht ganz einfach zu sein scheint
    echo '(real) request: <b>'.$page->getRequestMapper()->getUri().'</b><br/>'; // TODO soweit ich das beurteilen kann ist das hier schon falsch und sollte eigentlich nicht /404 sondern /tests sein - daher rest auskommentiert
    // => ... eigentlich ist es nicht wirklich falsch - siehe großen kommentar oben drüber

    /*
    echo 'expecting $..->getUri() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($page->getRequestMapper()->getUri());
    // assertion
    echo getAssertionResult($page->getRequestMapper()->getUri() === $expecting);
    // -- assertion
    echo '<br/>';
    echo 'expecting $..->pageFileExists() to be';
    var_dump($expecting2);
    echo 'result: ';
    $rm = $page->getRequestMapper();
    var_dump($rm->pageFileExists());
    // assertion
    echo getAssertionResult($rm->pageFileExists() === $expecting2);
    // -- assertion
    */

})(true);

echo '</section>';

/*
 * Override tests
 */
echo '<section>';
echo '<h1>override tests</h1>';
echo '<div>(test the override functionality that allows programmatically overriding pages an request mapper internal instances with other)</div>';

(function ($run=true) {
    if(!$run)
        return;

    // simple working request mapper
    $rm = new RequestMapper($req1 = '/this/exists');
    $page = $rm->getPage(); // so this is the test page we want to use for overriding

    // fake request mapper for testing overriding func.
    //$rm2 = new RequestMapper($req2 = '/quxx/does/not/exist');
    $rm2 = CurrentRequest::inst()->getRequestMapper();
    $req2 = $rm2->getUri();
    $rm2->overridePage($page);

    $expecting = true;
    $expecting2 = 'exists';
    echo 'using current request: <b>'.$req2.'</b><br/>';
    echo 'but overriding page with page of the request: <b>'.$req1.'</b><br/>';
    echo 'expecting $..->pageFileExists() to be';
    var_dump($expecting);
    echo 'result: ';
    var_dump($rm->pageFileExists());
    // assertion
    echo getAssertionResult($rm->pageFileExists() === $expecting);
    // -- assertion
    echo '<br/>';
    echo 'expecting $..->getPage()->getName() to be';
    var_dump($expecting2);
    echo 'result: ';
    var_dump($rm->getPage()->getName());
    // assertion
    echo getAssertionResult($rm->getPage()->getName() === $expecting2);
    // -- assertion

    echo '<hr/>';
})(true && !$disable_all);

echo '</section>';

/*
 * ML tests
 */
echo '<section>';
echo '<h1>multilang tests</h1>';
echo '<div>up to come...</div>';
echo '</section>';

/*
 * weitere tests:
 * ändern der 404 seite auf eine eigene...
 */


/*
 * TODO
 * - eine reset() / resetOverrides() methode implementieren die die singleton instanz zurücksetzt (damit man ein override wieder rückgängig machen kann)
 */
