<?php

class RequestMapper {

    use TRequestMapperBasePaths;
    use TRequestMapperProxyMethods;
    use TStatusCodes;
    use TRequestMapperUtils;

    private ?string $uri = null;
    private ?string $original_request_path = null; // the original request path (for example '/test///foo/bar///quxx/') without any query string
    private ?string $original_request_query = null;
    private ?RequestMapperConfig $config = null;
    public static ?RequestMapperConfig $global_config = null;

    private ?string $instanced_by = null; // can internally be used to store who instanced this class - if its null it can be considered user-code/implementation instanced
    public function setInstancedBy(string $instanced_by) : void {
        $this->instanced_by = $instanced_by;
    }
    public function getInstancedBy() : string|null {
        return $this->instanced_by;
    }

    private $page_file_exists = null;

    private ?IPage $page = null;

    public function __construct(string $uri, RequestMapperConfig $config = null, ?Closure $beforeRun = null) {
        $this->config = $config; // when for example using CurrentRequest $config will never be passed - note that $this->getConfig() trys to get the config from $this->config or alternatively from self::global_config
        if($beforeRun)
            $beforeRun($this);
        $this->run($uri);
    }

    public static function setGlobalConfig(RequestMapperConfig $config) {
        self::$global_config = $config;
    }

    public static function getGlobalConfig() : RequestMapperConfig|null {
        return self::$global_config;
    }

    public function setConfig(RequestMapperConfig $config) : void {
        $this->config = $config;
    }

    /**
     * @return RequestMapperConfig local config or fall back to the global config if a local config is not set
     */
    public function getConfig () : RequestMapperConfig|null {
        return $this->config ?? self::$global_config ?? RequestMapperConfig::createDefaultConfig();
    }

    public function update() : void {
        $this->run($this->uri);
    }

    private function run (string $uri) : void { // called by the constructor and the update() method

        $uri_without_query = strtok($uri, '?');
        $this->original_request_path = $uri_without_query;
        $this->original_request_query = substr($uri, strlen($uri_without_query));

        if($this->instanced_by === Default404Page::class) // TODO und was wenn jemand seine eigene 404 page implementation übergibt?? dann geht das nicht..
            $this->uri = '####TODO TODO TODO###';
        //if($this->instanced_by === CurrentPage::class)
        //    $this->uri = '?FOOBAR';
        //if($this->instanced_by === CurrentRequest::class)
            //$this->uri = 'FOOBAR!';
        else
            $this->uri = $uri = self::cleanUri($uri_without_query);

        $dest_file = ($uri === '' ? $this->getConfig()->getDefaultPage() : $uri); // dest file without extension ; dest file may be not existing (checks following later)

        $this->applyBasePathStrip($dest_file);

        //$page_base = ltrim($this->getFileBaseDir(), '/');
        //$page_base = $this->getFileBaseDir();
        $page_base = $this->getBasePathConfig()->getBasePath();

        //$filePath = WebFrame::inst()->blade->filePath($dest_file, $page_base);
        //$filePath = filePath($dest_file, $page_base); // only for this dev version - when everything works use line above
        $filePath = $this->filePath($dest_file, $page_base);

        //d($filePath);

        //dd($page_base, $filePath);

        /** @var $page IPage */

        if($this->isDetailPageRequest()) {
            // $this->page_file_exists = ???; // without setting this it will be null ... dunno if this is fine
            $page = new DetailPage($this, $filePath);
            $this->page_file_exists = true; // TODO not sure if we should return true because the detail page exists, but it is not the actually requested page but some kind of virtual page..
        }
        elseif($filePath && is_file($filePath)) { // simple existing page
            $this->page_file_exists = true;
            $page = new Page($this, $filePath);
        }
        else { // page not found
            $this->page_file_exists = false;
            //d($this->getBasePathConfig());
            //$page = $this->getConfig()->get404Page();
            $page = $this->getBasePathConfig()->getFourOFourPage();
        }

        $this->page = $page;
    }

    public function needsRedirect() {
        $nr = $this->getRedirectUri();
        return $this->getRedirectUri() === null ? null : $nr !== false;
    }

    /**
     * TODO I removed tons of ML stuff here... I need to check if still everything works
     * @return string|false|null => string if we need a redirect; false if we don't need a redirect ; null if it does not matter because we will be running into a 404 what will never require a redirect
     */
    public function getRedirectUri(string $prefix = '') : string|bool|null { // old name: getRedirectPath

        if(!$this->pageFileExists() && !$this->isDetailPageRequest()) // because if the page file does not exist we are setting up the 404 page and the 404 page does not require a redirect in any form
            return null;

        $uri = $this->uri;

        $redirect_path = null;

        $route_map = array_merge([
            'index.php' => '/',
            $this->getConfig()->getDefaultPage() => '/' // redirect the default page (for example '/home') to '/'
        ], $this->getConfig()->getRouteMap());
        if(in_array($uri, array_keys($route_map))) {
            //d('1');
            $redirect_path = $route_map[$uri];
        }
        elseif($this->isRequestDoubleBase()) {
            //d('2');
            $redirect_path = rtrim(substr(
                $this->uri,
                0,
                strrpos($this->uri, basename($this->uri))
            ), '/');
        }
        elseif($this->original_request_path !== $this->uri) { // $this->uri is already a clean uri version (without tailing and possible double middle slashed) - so if the original request differs from this clean version we need a redirect
            //d('3');
            $redirect_path = $this->uri;
        }

        if($redirect_path !== null)
            return $prefix . $redirect_path . ($this->original_request_query ?? '');

        return false;
    }

    /*
     * When a desired page could actually be accessed for example with >/subdir/foo< and with >/subdir/foo/foo< (this happens when the dir "foo" contains a "foo.blade.php")
     * we are having duplicate content because the content is avail at both urls.
     * So we need to prevent this from being valid for the RequestMapper.
     *
     * This method checks if the requested uri's basename is the same as the dirname of the requested uri.
     * To make this more clear: It checks if chunk 1 is the same as chunk 2 (resulting in a truthy return):
     * Example Request: /subdir/<chunk 1:>foo/<chunk 2:>foo -> method returns true
     * instead of:
     * Example Request: /subdir/<chunk 1:>foo/<chunk 2:>bar -> method returns false (because the (basename of the) dirname 'foo' (chunk 1) differs from the uri's basename 'bar' (chunk 2))
     */
    private function isRequestDoubleBase() : bool { // I cannot think of a better name for this method
        $pn = pathinfo($this->uri);
        return basename($pn['dirname']) === $pn['basename'];
    }

    /*private function _getLanguage() {
        return Config::MULTI_LANGUAGE ? ( ($lfu = WebFrame::inst()->getLanguageFromUri($this->getUri(), Config::LANGUAGES)) === false ? Config::DEFAULT_LANGUAGE : $lfu ) : false;
    }*/

    /*
     * get lang abbreviation from the URI
     * - expecting the lang abbreviation to be the first uri part
     */
    /*public function getLanguageAbbreviationFromUri() {
        if(!$this->getConfig()->usesMultilanguage())
            return null;

        $uri = $this->uri;

        $mlConfig = $this->getConfig()->getMultiLanguageConfig();

        $possible_lang = explode('/', ltrim($uri, '/'))[0];

        $maxLangAbbreviationLength = $mlConfig->getMaxLangAbbreviationLength();
        $minLangAbbreviationLength = $mlConfig->getMinLangAbbreviationLength();

        $allowedLangs = $mlConfig->getAllowedLangs();

        if(strlen($possible_lang) > $maxLangAbbreviationLength || strlen($possible_lang) < $minLangAbbreviationLength || !in_array($possible_lang, $allowedLangs))
            return $mlConfig->getDefaultLang();

        return $possible_lang;
    }*/

    /*
     * if page file does not exist we don't need a redirect because it will cause the 404 page to be shown
     * note that pageFileExists also returns true if the uri is not clean - so if there is a page file: pages/foo/quxx.blade.php and you request /foo/quxx///// or something - pageFileExists will return true anyways
     */
    public function ensureCleanUri () {
        if($this->pageFileExists() && $this->needsRedirect())
            $this->reroute($this->getRedirectUri());
    }

    public function isDetailPageRequest () : bool|null {
        $dynamic_detail_page_enabled = $this->getConfig()->isDynamicDetailPageEnabled();
        return $dynamic_detail_page_enabled ? (str_contains($this->getUri(), '/detail/')) : null;
    }

    public function getPage () : ?IPage {
        return $this->page;
    }

    public function overridePage (IPage $page) : void {
        $this->page = $page;
        $this->run($page->getUri());
    }

    public function getUri () : ?string {
        return $this->uri;
    }

    public function pageFileExists() {
        return $this->page_file_exists;
    }


}
