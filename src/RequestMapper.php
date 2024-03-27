<?php

namespace serjoscha87\phpRequestMapper;

class RequestMapper {

    private ?string $uri = null;
    private ?string $original_request_path = null; // the original request path (for example '/test///foo/bar///quxx/') without any query string
    private ?string $original_request_query = null;
    private ?RequestMapperConfig $config = null;
    public static ?RequestMapperConfig $global_config = null; 

    public static $DETERMINE_INSTANCED_BY = false; // if true the instanced_by property will be set to the class name that instanced the RequestMapper instance
    private ?string $instanced_by = null; // can internally be used to store who instanced this class - if its null it can be considered user-code/implementation instanced
    public function setInstancedBy(string $instanced_by) : void {
        $this->instanced_by = $instanced_by;
    }
    public function getInstancedBy() : string|null {
        return $this->instanced_by;
    }

    private ?bool $page_file_exists = null;

    private bool $is_detail_page_request = false;
    private ?string $detail_page_query = null;

    private ?IPage $page = null;

    private array $CACHE = [];

    /**
     * @param string $uri the request to run the mapper against
     * @param RequestMapperConfig|null $config if null the global config will be used or if no global config is confiured a default config will be used
     */
    public function __construct(string $uri, RequestMapperConfig $config = null) {
        if($config === null && self::$global_config === null)
            $this->config = RequestMapperConfig::createDefaultConfig(); // this will fix the config to the global config at the point the request mapper instance is created. This means changing the global config afterward the RequestMapper instantiation will not affect the RequestMapper instance
        elseif($config !== null)
            $this->setConfig($config);

        if(self::$DETERMINE_INSTANCED_BY)
            $this->setInstancedBy(debug_backtrace()[1]['class']);

        $this->run($uri);
    }

    public static function setGlobalConfig(RequestMapperConfig $config) {
        self::$global_config = $config;
    }

    public static function getGlobalConfig() : RequestMapperConfig|null {
        return self::$global_config;
    }

    public function setConfig(RequestMapperConfig $config) : self {
        $config->setRequestMapper($this);
        $this->config = $config;
        $this->run($this->getUri());
        return $this;
    }

    /**
     * @return RequestMapperConfig local config or fall back to the global config if a local config is not set
     */
    public function getConfig () : RequestMapperConfig|null {
        return $this->config ?? self::$global_config;
    }

    public function update($uri = null) : void {
        $this->run($uri ?? $this->uri);
    }

    private function run (string $uri) : void { // called by the constructor and the update() method

        $this->CACHE = [];
        $this->is_detail_page_request = false;
        $this->detail_page_query = null;

        $uri_without_query = strtok($uri, '?');

        if(empty($uri_without_query))
            $uri_without_query = '/';
        $this->original_request_path = $uri_without_query;
        $this->original_request_query = substr($uri, strlen($uri_without_query));

        $this->uri = $uri = self::cleanUri($uri_without_query); // the exact request path (cleaned and with leading '/')

        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => /* @var $config BasePathConfig */ $config) // TODO the request mapper class has this loop 3 times... at least this and applyBasePathStrip could be combined...
            $config->setRequestBase($url);

        $dest_file = ($this->isUriEmpty() ? $this->getDefaultPage() : $uri); // dest file without extension ; dest file may be not existing (checks following later)

        $this->applyBasePathStrip($dest_file); // TODO now that we have the keys of the base path we could perhaps spare out the foreach loop @ applyBasePathStrip ; like so:
        // => $this->getCombineBasePaths()[$this->findBasePathConfig()->getRequestBase()] ... hier dann weitere logik aus applyBasePathStrip zum ersetzen

        if($this->getConfig()->isDynamicDetailPageEnabled() && str_contains($uri, $dpi = ('/' . $this->getDetailPageIdentifier() . '/'))) {
            $this->is_detail_page_request = true;
            $this->detail_page_query = substr($uri, strpos($uri, $dpi) + strlen($dpi));
        }

        $page_base = $this->findBasePathConfig()->getBasePath();

        $filePath = $this->filePath($dest_file, $page_base);

        /** @var $page IPage instance of a class that implements IPage */

        if($this->isDetailPageRequest()) {
            $this->page_file_exists = true;
            $page = new DetailPage($this, $filePath, $this->detail_page_query);
        }
        elseif($filePath && is_file($filePath)) { // simple existing page
            $this->page_file_exists = true;
            $page = new Page($this, $filePath);
        }
        // TODO man kann detailseiten aufrufen ohne die query dahinter - denke das sollte unterbunden werden weil die detailseiten ja eig immer von einer query / params abhängen
        else { // page not found / 404
            $this->page_file_exists = false;
            $page = new ($this->findBasePathConfig()->get404PageClass())($this);
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

        $uri = $this->uri;

        if(str_ends_with($uri, 'index.php'))
            return '/';

        if(!$this->pageFileExists() && !$this->isDetailPageRequest()) // because if the page file does not exist we are setting up the 404 page and the 404 page does not require a redirect in any form
            return null;

        $redirect_path = null;

        $route_map = $this->getConfig()->getRouteMap();
        if(in_array($uri, array_keys($route_map))) {
            $redirect_path = $route_map[$uri];
        }
        elseif($this->isDefaultPageRequest()) { // redirect the default page (for example '/home') to '/'
            $redirect_path = $this->getRequestBase();
        }
        elseif($this->isRequestDoubleBase()) {
            $redirect_path = rtrim(substr(
                $uri,
                0,
                strrpos($uri, basename($uri))
            ), '/');
        }
        elseif($this->original_request_path !== $uri) { // $this->uri is already a clean uri version (without tailing and possible double middle slashed) - so if the original request differs from this clean version we need a redirect
            $redirect_path = $uri;
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
        return basename($pn['dirname']) === $pn['basename'] && !$this->isUriEmpty();
    }

    public function isDefaultPageRequest () : bool {
        $requestBaselessUri = str_replace($this->getRequestBase(), '', $this->uri);
        return $requestBaselessUri === $this->getDefaultPage() || $this->uri === $this->getDefaultPage();
    }

    /**
     * Returns true/false whether the current request is a detail page request or not or null if the dynamic detail page feature is disabled
     */
    public function isDetailPageRequest () : bool|null {
        return $this->is_detail_page_request;
    }

    public function getPage () : null|IPage|DetailPage {
        return $this->page;
    }

    public function overridePage (IPage $page) : void {
        $this->page = $page;
        $pageUri = $page->getRequestMapper()->getUri();
        if($pageUri !== $this->uri)
            $this->run($pageUri);
    }

    public function getUri () : ?string {
        return $this->uri;
    }

    public function pageFileExists() {
        return $this->page_file_exists;
    }

    public function getDefaultPage () : ?string {
        if(isset($this->CACHE['defaultPage']))
            return $this->CACHE['defaultPage'];
        return $this->CACHE['defaultPage'] = $this->findBasePathConfig()->getDefaultPage() ?? $this->getConfig()->getDefaultDefaultPage();
    }
    public function getFileExtension () : ?string {
        if(isset($this->CACHE['fileExtension']))
            return $this->CACHE['fileExtension'];
        return $this->CACHE['fileExtension'] = $this->findBasePathConfig()->getPageFileExtension() ?? $this->getConfig()->getDefaultPageFileExtension();
    }
    /**
     * @return string|null the base/root path where the page files are located
     */
    public function getBasePath () : ?string {
        if(isset($this->CACHE['basePath']))
            return $this->CACHE['basePath'];
        return $this->CACHE['basePath'] = $this->findBasePathConfig()->getBasePath();
    }
    public function getDetailPageIdentifier () : ?string {
        if(isset($this->CACHE['detailPageIdentifier']))
            return $this->CACHE['detailPageIdentifier'];
        return $this->CACHE['detailPageIdentifier'] = $this->findBasePathConfig()->getDetailPageIdentifier() ?? $this->getConfig()->getDefaultDetailPageIdentifier();
    }
    public function getRequestBase () {
        if(isset($this->CACHE['requestBase']))
            return $this->CACHE['requestBase'];
        return $this->CACHE['requestBase'] = $this->findBasePathConfig()->getRequestBase();
    }

    public function isUriEmpty() : bool {
        return $this->uri === $this->findBasePathConfig()->getRequestBase();
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
        $base = $base ?? /* @see BasePathConfig obj > */$this->findBasePathConfig()->getBasePath();
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
            $page_file = substr($uri, 0, strpos($uri, $this->detail_page_query));
            $file = sprintf('%s%s%s', $base, rtrim($page_file, '/'), $extension);
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

    private function getCombineBasePaths () : array {
        if(isset($this->CACHE['basePathsCombined']))
            return $this->CACHE['basePathsCombined'];
        return $this->CACHE['basePathsCombined'] = array_merge(
            $this->getConfig()->getFurtherBasePaths(), // array of BasePathConfig
            self::$globalBasePaths,
            ['/' => $this->getConfig()->getDefaultBasePathConfig()] // << the main BasePathConfig instance passed to the RequestMapperConfig as the first param
        );
    }

    /**
     * search through all configured base paths and apply(/strip) the 'strip'-string to the passed uri if a basepath was found for the passed uri
     * note that this does nothing at all if no basepath config was found for the current request
     *
     * This method is automatically called by the RequestMapper class with the run & update method
     *
     * @param string &$destFile the path ref to the file the stripping should be applied to - note that this method will modify the passed string
     */
    private function applyBasePathStrip(string &$destFile) : void {
        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => /* @var $config BasePathConfig */ $config) {
            $url = ltrim($url, '/'); 
            if(str_contains($this->uri, $url)) {
                if($config->getStrip() === BasePathConfig::STRIP_REQUEST_BASE) {
                    $destFile = str_replace(
                        //($config->getStrip() === BasePathConfig::STRIP_REQUEST_BASE) ? $url : $config->getStrip(),
                        $url,
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
    public function findBasePathConfig () : BasePathConfig|null {
        if(isset($this->CACHE['basePathConfig']))
            return $this->CACHE['basePathConfig'];
        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => $config /* @var $config BasePathConfig */) {
            $url = ltrim($url, '/');
            if(str_contains($this->uri, $url)) {
                $this->CACHE['basePathConfig'] = $config;
                return $config;
            }

        }
        return null;
    }

    /*
     * proxy methods
     */
    public function registerBasePath (string $url, BasePathConfig $config) {
        $this->getConfig()->registerBasePath($url, $config);
    }

}
