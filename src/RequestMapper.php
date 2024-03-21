<?php

namespace phpRequestMapper;

class RequestMapper {

    use TStatusCodes;

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

    /**
     * @param string $uri the request to run the mapper against
     * @param RequestMapperConfig|null $config if null the global config will be used or if no global config is confiured a default config will be used
     * @param Closure|null $beforeRun allows to inject logic before the mapper runs
     */
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

    public function setConfig(RequestMapperConfig $config) : self {
        $this->config = $config;
        $this->run($this->getUri());
        return $this;
    }

    /**
     * @return RequestMapperConfig local config or fall back to the global config if a local config is not set
     */
    public function getConfig () : RequestMapperConfig|null {
        $config = $this->config ?? self::$global_config ?? RequestMapperConfig::createDefaultConfig();
        $config->setRequestMapper($this);
        return $config;
    }

    public function update($uri = null) : void {
        $this->run($uri ?? $this->uri);
    }

    private function run (string $uri) : void { // called by the constructor and the update() method

        $uri_without_query = strtok($uri, '?');
        if(empty($uri_without_query))
            $uri_without_query = '/';
        $this->original_request_path = $uri_without_query;
        $this->original_request_query = substr($uri, strlen($uri_without_query));

        $this->uri = $uri = self::cleanUri($uri_without_query); // the exact request path (cleaned and with leading '/')

        $dest_file = ($this->isUriEmpty() ? $this->getDefaultPage() : $uri); // dest file without extension ; dest file may be not existing (checks following later)

        $this->applyBasePathStrip($dest_file);

        $page_base = $this->getBasePathConfig()->getBasePath();

        $filePath = $this->filePath($dest_file, $page_base);

        /** @var $page IPage */

        if($this->isDetailPageRequest()) {
            $page = new DetailPage($this, $filePath);
            $this->page_file_exists = true; 
        }
        elseif($filePath && is_file($filePath)) { // simple existing page
            $this->page_file_exists = true;
            $page = new Page($this, $filePath);
        }
        else { // page not found
            $this->page_file_exists = false;
            $page = $this->getBasePathConfig()->getFourOFourPage();
        }

        $this->page = $page;
    }

    public function needsRedirect() {
        $nr = $this->getRedirectUri();
        return $nr === null ? null : $nr !== false;
    }

    /**
     * @return string|false|null => string if we need a redirect; false if we don't need a redirect ; null if it does not matter because we will be running into a 404 what will never require a redirect
     */
    public function getRedirectUri(string $prefix = '') : string|bool|null { // old name: getRedirectPath

        if(!$this->pageFileExists() && !$this->isDetailPageRequest()) // because if the page file does not exist we are setting up the 404 page and the 404 page does not require a redirect in any form
            return null;

        $uri = $this->uri;

        $redirect_path = null;

        $route_map = array_merge([
            'index.php' => '/',
        ], $this->getConfig()->getRouteMap());
        if(in_array($uri, array_keys($route_map))) {
            $redirect_path = $route_map[$uri];
        }
        elseif($this->getDefaultPage() ===  $uri) { // redirect the default page (for example '/home') to '/'
            $redirect_path = '/';
        }
        elseif($this->isRequestDoubleBase()) {
            $redirect_path = rtrim(substr(
                $this->uri,
                0,
                strrpos($this->uri, basename($this->uri))
            ), '/');
        }
        elseif($this->original_request_path !== $this->uri) { // $this->uri is already a clean uri version (without tailing and possible double middle slashed) - so if the original request differs from this clean version we need a redirect
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
        return basename($pn['dirname']) === $pn['basename'] && $this->uri !== '/';
    }

    /**
     * Returns true/false whether the current request is a detail page request or not or null if the dynamic detail page feature is disabled
     */
    public function isDetailPageRequest () : bool|null {
        $dynamic_detail_page_enabled = $this->getConfig()->isDynamicDetailPageEnabled();
        return $dynamic_detail_page_enabled ? (str_contains($this->getUri(), '/detail/')) : null;
    }

    public function getPage () : null|IPage|DetailPage {
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

    /*
     * - getDefaultPage
     * - getFileExtension
     * => find the correct property... ..... shoul be documented right - but don't even know how to describe this
     */
    public function getDefaultPage () : ?string {
        return $this->getBasePathConfig()->getDefaultPage() ?? $this->getConfig()->getDefaultDefaultPage();
    }
    public function getFileExtension () : ?string {
        return $this->getBasePathConfig()->getPageFileExtension() ?? $this->getConfig()->getDefaultPageFileExtension();
    }

    public function isUriEmpty() : bool {
        return $this->uri === '/';
    }

    public static function isReal404() {
        $is_user_triggered_404 = strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false; // none user triggered: all requests that do not expect back a mime type of text/html
        return $is_user_triggered_404 === false;
    }

    /**
     * Returns a clean formatted uri that always has the same pattern
     * Examples:
     *    /home///test/      =>     /home/test
     *    home/test          =>     /home/test
     *    /home/test         =>     /home/test
     *    /home/test/        =>     /home/test
     *    /                  =>     /
     *    <none>             =>     /
     * @param string $uri2clean
     * @return string the clean and expective path
     */
    static function cleanUri(string $uri2clean) : string {
        return '/' . implode('/', array_filter(explode('/', $uri2clean)));
    }

    private function filePath($uri = null, $base = null) {
        $uri = $uri ?? $this->uri; 
        /* @var $base string */
        $base = $base ?? /* BasePathConfig obj > */$this->getBasePathConfig();
        $extension = $this->getFileExtension();
        if (is_file($f = sprintf('%s%s%s', $base, $uri, $extension))) {
            /*
              * example: for resolving requests like this
              * /foobar/test
              * to fs structure like this:
              * /pages/foobar/test.blade.php
              */
            $file = $f;
        }
        elseif($this->isDetailPageRequest()) {
            $page_and_query = [];
            preg_match('/^(?<page_file>.*\/detail)\/(?<query>.*)$/', $this->uri, $page_and_query);
            $page_base = $this->getBasePathConfig()->getBasePath();
            if($this->getConfig()->doesDynamicDetailPageQueryOverrideGet())
                $_GET['query'] = $page_and_query['query'];
            return $this->filePath($page_and_query['page_file'], $page_base);
        }
        elseif($f = sprintf('%s%s/%s%s', $base, $uri, basename($uri), $extension)) {
            /*
             * example: for resolving requests like this:
             * /foobar/test
             * to fs structure like this:
             * /pages/foobar/test/test.blade.php
             */
            $file = $f;
        }
        else
            $file = null;

        return $file;
    }

    /*
     * the global base path is available through ALL Page instances that may exist
     */
    public static $globalBasePaths = [];
    public static function registerGlobalBasePath($path, BasePathConfig $config) {
        self::$globalBasePaths[$path] = $config;
    }
    
    /*
     * both combine basepaths into a local variable and the return this generated var
     */
    protected $basePathsCombined = null;
    private function getCombineBasePaths () : array {
        /* @var $this->config RequestMapperConfig */
        $this->basePathsCombined = array_merge(
            $this->getConfig()->getFurtherBasePaths(), // array of BasePathConfig
            self::$globalBasePaths,
            ['/' => $this->getConfig()->getDefaultBasePathConfig()] // << the main BasePathConfig instance passed to the RequestMapperConfig as the first param
        );
        return $this->basePathsCombined;
    }

    /**
     * search through all configured base paths and apply(/strip) the 'strip'-string to the passed uri if a basepath was found for the passed uri
     * note that this does nothing at all if no basepath config was found for the current request
     *
     * This method is automatically called by the RequestMapper class with the run & update method
     *
     * @param $destFile
     * @return void
     */
    public function applyBasePathStrip(&$destFile) : void {
        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => /* @var $config BasePathConfig */ $config) {
            $url = ltrim($url, '/'); 
            if(str_contains($this->uri, $url)) {
                if($config->getStrip() === BasePathConfig::STRIP_REQUEST_BASE) {
                    $destFile = str_replace(
                        ($config->getStrip() === BasePathConfig::STRIP_REQUEST_BASE) ? $url : $config->getStrip(),
                        '',
                        $destFile
                    );
                }
            }
        }
    }

    /**
     * Find the correct BasePath config while considering the default base path and all further registered base paths
     * @return BasePathConfig|null
     */
    private ?BasePathConfig $basePathConfig = null;
    public function getBasePathConfig () : BasePathConfig|null {
        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => $config /* @var $config BasePathConfig */) {
            $url = ltrim($url, '/');
            if(str_contains($this->uri, $url)) {
                $this->basePathConfig = $config; // cache found config
                return $config;
            }

        }

        $config = $this->getConfig(); /* @var $config RequestMapperConfig */
        $this->basePathConfig = $config->getDefaultBasePathConfig(); // cache config
        return $this->basePathConfig;
    }

    /*
     * proxy methods
     */
    public function registerBasePath (string $url, BasePathConfig $config) {
        $this->getConfig()->registerBasePath($url, $config);
    }

}
