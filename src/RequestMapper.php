<?php

declare(strict_types=1);

namespace serjoscha87\phpRequestMapper;

/**
 * Class description lorem ipsum
 *
 * Methods delegated to the current mapping object:
 * @method getSolidRequestBase - delegated to the current mapping object @see Mapping::<methodName>
 * @method isDefaultMapping - delegated to the current mapping object @see Mapping::<methodName>
 * @method setDefaultPagePath - delegated to the current mapping object @see Mapping::<methodName>
 * @method getDefaultPagePath - delegated to the current mapping object @see Mapping::<methodName>
 * @method setPageBasePath - delegated to the current mapping object @see Mapping::<methodName>
 * @method getPageBasePath - delegated to the current mapping object @see Mapping::<methodName>
 * @method set404PageClass - delegated to the current mapping object @see Mapping::<methodName>
 * @method get404PageClass - delegated to the current mapping object @see Mapping::<methodName>
 * @method set200PageClass - delegated to the current mapping object @see Mapping::<methodName>
 * @method get200PageClass - delegated to the current mapping object @see Mapping::<methodName>
 * @method setPageFileExtension - delegated to the current mapping object @see Mapping::<methodName>
 * @method getPageFileExtension - delegated to the current mapping object @see Mapping::<methodName>
 * @method setDetailPageEnabled - delegated to the current mapping object @see Mapping::<methodName>
 * @method isDetailPageEnabled - delegated to the current mapping object @see Mapping::<methodName>
 * @method setDetailPageClass - delegated to the current mapping object @see Mapping::<methodName>
 * @method getDetailPageClass - delegated to the current mapping object @see Mapping::<methodName>
 * @method setDetailPageIdentifier - delegated to the current mapping object @see Mapping::<methodName>
 * @method getDetailPageIdentifier - delegated to the current mapping object @see Mapping::<methodName>
 * @method setStrip - delegated to the current mapping object @see Mapping::<methodName>
 * @method getStrip - delegated to the current mapping object @see Mapping::<methodName>
 * @method getRouteMap - delegated to the current mapping object @see Mapping::<methodName>
 * @method setRouteMap - delegated to the current mapping object @see Mapping::<methodName>
 */
class RequestMapper {

    // TODO unify variable naming -> .._.. vs no .._..

    private SolidUri $solidUri;

    private ?Mapping $currentMapping = null;

    private ?array $mappings;
    public static array $global_mappings = [];

    private ?bool $page_file_exists = null;

    private bool $is_detail_page_request = false;

    private ?IPage $page = null;

    public static $DETERMINE_INSTANCED_BY = false; // if true the instanced_by property will be set to the class name that instanced the RequestMapper instance
    private ?string $instanced_by = null; // can internally be used to store who instanced this class - if its null it can be considered user-code/implementation instanced

    /**
     * @param array<IDefaultMapping>|IDefaultMapping|null $mappings if null, the global config will be used, or if no global config is configured, a default config will be used
     * @param bool $run whether to run the request mapper immediately after instantiation
     */
    public function __construct(array|IDefaultMapping|null $mappings = null, bool $run = false) {

        if($mappings instanceof IDefaultMapping)
            $mappings = [$mappings];

        if(self::$DETERMINE_INSTANCED_BY)
            $this->instanced_by = debug_backtrace()[1]['class'];

        $this->mappings = $mappings;

        if($run)
            $this->run();

    }

    public function update(string $uri = null) : void {
        $this->run($uri ?? $this->solidUri);
    }

    public function requestMatches(Mapping &$mapping, bool $exact = false) {
        if($mapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK)
            return (\Closure::bind($mapping->getCustomRequestBaseCheck(), $mapping))($mapping, $this->solidUri);
        return $exact ? $this->solidUri->getUri() === $mapping->getSolidRequestBase() : str_starts_with($this->solidUri->getUri(), $mapping->getSolidRequestBase());
    }

    public function run (string|SolidUri|null $uri = null) : void {

        $uri ??= $_SERVER['REQUEST_URI'];

        $this->solidUri = $solidUri = $uri instanceof SolidUri ? $uri->getUri() : new SolidUri($uri);

        if(empty($this->mappings) && empty(self::$global_mappings))
            $mappings = [Mapping::createDefault()];
        else
            $mappings = $this->mappings ?? self::$global_mappings;

        $defaultMappings = 0;
        $defaultMapping = null; // (this can always be just a single one)

        foreach ($mappings as $mapping) {
            /* @var $mapping Mapping */

            if($mapping->isDefaultMapping()) {
                $defaultMappings++;
                if($defaultMappings > 1)
                    throw new \Exception('Multiple default mappings found - please only pass one default mapping and use concrete mappings for the rest of the mappings. You can do so by using the createFor factory method instead of createDefault.');

                $defaultMapping = $mapping;
                continue; // skip default mapping for the check because they need to checked with a lower priority then the concrete mappings
            }

            if($this->requestMatches($mapping))
                $this->currentMapping = $mapping;

            // break the loop after the default mapping and the first matching mapping is found
            if($this->currentMapping !== null && $defaultMapping !== null)
                break;
        }

        // if no specific mapping was found, fall back to the default mapping
        if($this->currentMapping === null)
            $this->currentMapping = $defaultMapping;

        if(!$this->currentMapping)
            throw new \Exception('No mapping found for the current request: ' . $uri. '. You also do not have a default mapping! Please make sure to always have a default mapping!');

        $this->currentMapping->setRequestMapper($this);

        // Destination file without an extension. Note that the dest-file may be not existing (checks following later)
        $str_SolidDestFilePath = ($this->requestIsMappingRequestBase() ? (new SolidUri($this->getDefaultPagePath()))->getUri() : $solidUri->getUri());

        $this->applyRequestBaseStrip($str_SolidDestFilePath); // (method also ensures the url stays solid)

        // TODO this is very fuzzy und will result in detail page request handling if the request just contains the detail page keyword
        $this->is_detail_page_request = $this->isDetailPageEnabled() && str_contains($str_SolidDestFilePath, ('/' . $this->getDetailPageIdentifier() . '/'));

        $str_PageBasePath = $this->getPageBasePath();

        $str_FullQualifiedPageFilePath = $this->getPageFilePath($str_SolidDestFilePath, $str_PageBasePath);

        /** @var $obj_Page IPage instance of a class that implements IPage */
        if($this->isDetailPageRequest()) {
            $this->page_file_exists = true;
            $str_DetailPageClass = $this->getDetailPageClass();

            $str_SolidUri = $solidUri->getUri();
            $pretty_query = substr( $str_SolidUri, strpos($str_SolidUri, $this->getDetailPageIdentifier()) + strlen($this->getDetailPageIdentifier()) + 1 ); // the default query string can still be accessed via $_GET
            $obj_Page = new $str_DetailPageClass($this, $str_FullQualifiedPageFilePath, $pretty_query); /** @see DetailPage (default) */
        }
        elseif($str_FullQualifiedPageFilePath && is_file($str_FullQualifiedPageFilePath)) { // simple existing page
            $this->page_file_exists = true;
            $str_200PageClass = $this->get200PageClass();
            $obj_Page = new $str_200PageClass($this, $str_FullQualifiedPageFilePath); /** @see Page (default) */
        }
        else { // page not found / 404
            $this->page_file_exists = false;
            $str_404PageClass = $this->get404PageClass(); /** @see Default404Page (default) */
            $obj_Page = new $str_404PageClass($this);
        }

        if($this->currentMapping->getOnMatchCallback() !== null)
            ($this->currentMapping->getOnMatchCallback())($obj_Page, $this->currentMapping, $this);

        if(!$obj_Page instanceof IPage)
            throw new \Exception('Page class does not implement IPage: ' . get_class($obj_Page) . ' - ' . $str_FullQualifiedPageFilePath);

        $this->page = $obj_Page;
    }

    public function needsRedirect() {
        $nr = $this->getRedirectUri();
        return $nr === null ? null : $nr !== false;
    }

    /**
     * @return string|false|null => string if we need a redirect; false if we don't need a redirect ; null if it does not matter because we will be running into a 404 what will never require a redirect
     */
    public function getRedirectUri(string $prefix = '') : string|bool|null {

        /*
         * TODO '/foobar/////bla?' fürt zu too many redirects
         * genau wie /foobar/detail/test-test?test
         */

        $str_SolidUri = $this->solidUri->getUri();

        if(str_ends_with($str_SolidUri, basename($_SERVER['SCRIPT_FILENAME']))) // redirect requests to the script file itself to the root path (most commonly '/index.php')
            return '/';

        if(!$this->pageFileExists() && !$this->isDetailPageRequest()) // because if the page file does not exist we are setting up the 404 page and the 404 page does not require a redirect in any form
            return null;

        $str_RedirectPath = null;

        $route_map = $this->currentMapping->getRouteMap();
        if(in_array($str_SolidUri, array_keys($route_map))) {
            $str_RedirectPath = $route_map[$str_SolidUri];
        }
        elseif($this->isDefaultPageRequest()) { // redirect the default page (for example '/home') to '/'
            $str_RedirectPath = $this->getSolidRequestBase();
        }
        elseif($this->isRequestDoubleBase()) { // for example, if we got this fs struc. (with default mappings): /pages/foo/foo.php and the request ist /foo/foo -> redirect to /foo
            $str_RedirectPath = rtrim(substr(
                $str_SolidUri,
                0,
                strrpos($str_SolidUri, basename($str_SolidUri))
            ), '/');
        }
        elseif($this->solidUri->getOriginalUri(false) !== $str_SolidUri) { // redirect unclean uri to clean uri
            $str_RedirectPath = $str_SolidUri;
        }

        if($str_RedirectPath !== null)
            return $prefix . $str_RedirectPath . ($this->solidUri->getQuery() ?? '');

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
        $pn = pathinfo($this->solidUri->getUri());
        return basename($pn['dirname']) === $pn['basename'] && !$this->isUriEmpty();
    }

    /**
     * Determines whether the current request would resolve to the configured default page for the current mapping
     * Like for example, when the request is '/home' and the default page is set to 'home.php' this method would return true while it would return false if the request is '/' (or of course something completely different like '/foobar')
     * or if the request is '/admin/dashboard' and the default page of this additional mapping is 'dashboard.php' this method would also return true while it will return false when the request is '/admin' or '/admin/foobar'
     */
    public function isDefaultPageRequest () : bool {
        if($this->currentMapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK)
            //$requestBaselessUri = $this->solidUri->getUri();
            return str_ends_with($this->solidUri->getUri(), $this->getDefaultPagePath());
        else
            $requestBaselessUri = str_replace($this->getSolidRequestBase(), '', $this->solidUri->getUri());
        return $requestBaselessUri === $this->getDefaultPagePath() || $this->solidUri->getUri() === $this->getDefaultPagePath();
    }

    /**
     * Returns true/false whether the current request is a detail page request or not or null if the dynamic detail page feature is disabled
     */
    public function isDetailPageRequest () : bool|null {
        return $this->is_detail_page_request;
    }

    public function getPage () : null|IPage {
        return $this->page;
    }

    public function overridePage (IPage $obj_Page) : void {
        $this->page = $obj_Page;
        //$str_PageUri = $obj_Page->getRequestMapper()->getUri();
        //if($str_PageUri !== $this->solidUri->getUri())
            //$this->run($str_PageUri);
        $this->update();
    }

    /**
     * @return string returns the request as solid uri string
     */
    public function getUri () : string {
        return $this->solidUri->getUri();
    }

    public function pageFileExists() : bool {
        return $this->page_file_exists;
    }

    public static function isReal404() : bool {
        $is_user_triggered_404 = strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false; // none user triggered: all requests that do not expect back a mime type of text/html
        return $is_user_triggered_404 === false;
    }

    /**
     * @param string|null $str_SolidUri if passed, this method assumes that it is a solid uri!
     */
    private function getPageFilePath(?string $str_SolidUri = null, ?string $str_BasePath = null) : ?string {
        $str_SolidUri ??= $this->solidUri->getUri();
        $str_BasePath ??= $this->getPageBasePath();
        $str_PageFileExtension = $this->getPageFileExtension();

        if (is_file($f = sprintf('%s%s%s', $str_BasePath, $str_SolidUri, $str_PageFileExtension))) {
            /*
              * example: for resolving requests like this
              * /foobar/test
              * to fs structure like this:
              * /pages/foobar/test.blade.php
              */
            $file = $f;
        }
        elseif ($this->isDetailPageRequest()) {
            $page_file = substr($str_SolidUri, 0, strpos($str_SolidUri, $this->getDetailPageIdentifier()));
            $file = sprintf('%s%s%s%s', $str_BasePath, $page_file, $this->getDetailPageIdentifier(), $str_PageFileExtension);
        }
        else {
            /*
             * example: for resolving requests like this:
             * /foobar/test
             * to fs structure like this:
             * /pages/foobar/test/test.blade.php
             */
            $f = sprintf('%s%s/%s%s', $str_BasePath, $str_SolidUri, basename($str_SolidUri), $str_PageFileExtension);
            if(is_file($f))
                $file = $f;
            else
                $file = null;
        }

        return $file;
    }

    public function isUriEmpty() : bool {
        return $this->solidUri->getUri() === '/';
    }

    /**
     * Determines whether the current request matches the current mapping's request base
     * For example if the request is '/admin' while we have a mapping initialized like this: Mapping::createFor('admin') the method would return true while it would be false for a request like '/admin/foobar'
     * or this method will also return true if the request is '/' because this will always match the default mapping while of course it will return false for any other request
     * @return bool|null returns null if a custom request base check is used - otherwise true/false for the matching result
     */
    private function requestIsMappingRequestBase() : ?bool {
        if($this->currentMapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK)
            return null;
        return $this->requestMatches($this->currentMapping, true);
    }

    /*
     * the global mappings are available through ALL RequestMapper instances that may exist.
     * But the RequestMapper always prioritizes the local mappings before the global mappings.
     */
    public static function setGlobalMappings(array/*<Mapping>*/ $mappings) {
        self::$global_mappings = $mappings;
    }
    public static function getGlobalMappings() : array|null {
        return self::$global_mappings;
    }
    public static function addGlobalMapping(Mapping $mapping) {
        self::$global_mappings[] = $mapping;
    }

    public function addMapping (Mapping $mapping) : self {
        $this->mappings[] = $mapping;
        return $this;
    }

    /**
     * Removes the configured string (fixed string or dynamic request base) from the passed file path while keeping the file path solid (the path passed is considered extension-less)
     *
     * This method is automatically called by the RequestMapper class with the run & update method
     *
     * @param string &$destFile the path ref to the file the stripping should be applied to - note that this method will modify the passed string
     */
    private function applyRequestBaseStrip(string &$destFile) : void {

        if($this->currentMapping->isDefaultMapping() || $this->currentMapping->getStrip() === null)
            return;

        if($this->currentMapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK)
            $url = null; // because we don't have a solid request base when using the custom request base check
        else
            $url = (new SolidUri($this->currentMapping->getSolidRequestBase()))->getUri();

        if($url === null || str_starts_with($this->solidUri->getUri(), $url)) {
            if($url !== null && $this->currentMapping->getStrip() === Mapping::STRIP_REQUEST_BASE)
                $destFile = (new SolidUri(str_replace($url, '', $destFile)))->getUri();
            else // < when 'strip' is a custom string
                $destFile = (new SolidUri(str_replace($this->currentMapping->getStrip(), '', $destFile)))->getUri();
        }
    }

    /*
     * redirects the request if needed - otherwise calls the implementer's closure to deliver the content
     */
    public function handle(\Closure $fn) : mixed {
        if(!RequestMapper::isReal404()) {
            $rm = $this;
            if($rm->needsRedirect()) {
                header('HTTP/1.0 308 Permanent Redirect');
                header('Location: ' . $rm->getRedirectUri());
                exit;
            } else {
                if($rm->getPage()->is404Page())
                    header('HTTP/1.0 404 Not Found');
                return $fn($rm->getPage());
            }
        }
        return null;
    }

    /*
     * try to delegate most method calls to the current mapping instance, so we have those methods proxied directly on the RequestMapper instance
     */
    public function __call(string $methodName, array $arguments) {
        if(in_array($methodName, [
            'getSolidRequestBase',
            'isDefaultMapping',
            'setDefaultPagePath',
            'getDefaultPagePath',
            'setPageBasePath',
            'getPageBasePath',
            'set404PageClass',
            'get404PageClass',
            'set200PageClass',
            'get200PageClass',
            'setPageFileExtension',
            'getPageFileExtension',
            'setDetailPageEnabled',
            'isDetailPageEnabled',
            'setDetailPageClass',
            'getDetailPageClass',
            'setDetailPageIdentifier',
            'getDetailPageIdentifier',
            'setStrip',
            'getStrip',
            'getRouteMap',
            'setRouteMap'
        ])) {
            return $this->currentMapping->$methodName(...$arguments);
        }
        throw new \Exception('Method does not exist on current mapping instance or is not allowed for delegation: ' . $methodName);
    }

    public function getInstancedBy() : string|null {
        return $this->instanced_by;
    }

}
