<?php

declare(strict_types=1);

namespace serjoscha87\phpRequestMapper;

/**
 * The Main RequestMapper class that handles the request mapping logic
 *
 *
 * Methods delegated to the current mapping object:
 *
 * @method getSolidRequestMatcherString
 *      @see Mapping::getSolidRequestMatcherString
 *
 * @method isDefaultMapping
 *      @see Mapping::isDefaultMapping
 *
 * @method setDefaultPagePath
 *      @see Mapping::setDefaultPagePath
 *
 * @method getDefaultPagePathSolid
 *      @see Mapping::getDefaultPagePathSolid
 *
 * @method setPageBasePath
 *      @see Mapping::setPageBasePath
 *
 * @method getPageBasePath
 *      @see Mapping::getPageBasePath
 *
 * @method set404PageClass
 *      @see Mapping::set404PageClass
 *
 * @method get404PageClass
 *      @see Mapping::get404PageClass
 *
 * @method set200PageClass
 *      @see Mapping::set200PageClass
 *
 * @method get200PageClass
 *      @see Mapping::get200PageClass
 *
 * @method setPageFileExtension
 *      @see Mapping::setPageFileExtension
 *
 * @method getPageFileExtension
 *      @see Mapping::getPageFileExtension
 *
 * @method setDetailPageEnabled
 *      @see Mapping::setDetailPageEnabled
 *
 * @method isDetailPageEnabled
 *      @see Mapping::isDetailPageEnabled
 *
 * @method setDetailPageClass
 *      @see Mapping::setDetailPageClass
 *
 * @method getDetailPageClass
 *      @see Mapping::getDetailPageClass
 *
 * @method setDetailPageIdentifier
 *      @see Mapping::setDetailPageIdentifier
 *
 * @method getDetailPageIdentifier
 *      @see Mapping::getDetailPageIdentifier
 *
 * @method setStrip
 *      @see Mapping::setStrip
 *
 * @method getStrip
 *      @see Mapping::getStrip
 *
 * @method getRouteMap
 *      @see Mapping::getRouteMap
 *
 * @method setRouteMap
 *      @see Mapping::setRouteMap
 *
 */
class RequestMapper {

    private bool $ran = false; // state var to determine whether the run method was called yet or not

    private SolidUri $solidUri;

    private ?Mapping $currentMapping = null;
    private ?Mapping $defaultMapping = null;

    private ?array $mappings;

    private ?bool $pageFileExists = null;

    private bool $isDetailPageRequest = false;

    private ?IPage $page = null;

    private array $callbacks = [];

    private bool $respectPriorities = false;

    public static ?RequestMapper $primaryInstance = null;
    public static array/*<*RequestMapper>*/ $furtherInstances = [];

    /**
     * @param array<IDefaultMapping>|IDefaultMapping|null $mappings if null, the global config will be used, or if no global config is configured, a default config will be used
     * @param bool $run whether to run the request mapper immediately after instantiation
     */
    public function __construct(array|IDefaultMapping|null $mappings = null, bool $run = false) {

        if($mappings instanceof IDefaultMapping)
            $mappings = [$mappings];

        $this->mappings = $mappings;

        if(self::$primaryInstance === null)
            self::$primaryInstance = &$this;

        if($run)
            $this->run();

    }

    public function update(string $uri = null) : void {
        if(isset($this->callbacks['beforeUpdate']))
            ($this->callbacks['beforeUpdate'])($this);

        $this->ran = false;
        $this->run($uri ?? $this->solidUri);

        if(isset($this->callbacks['afterUpdate']))
            ($this->callbacks['afterUpdate'])($this, $this->page);
    }

    public function requestMatches(Mapping &$mapping, ?int $overrideMatchingMethod = null) {

        if($mapping->isDefaultMapping())
            return $this->solidUri->getUri() === '/';

        if($mapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK)
            return (\Closure::bind($mapping->getRequestMatcher(), $mapping))($mapping, $this->solidUri, $this);

        return match($overrideMatchingMethod ?? $mapping->getMatchingMethod()) {
            Mapping::MATCHING_METHOD_EXACT                 => $this->solidUri->getUri() === $mapping->getRequestMatcher()->getUri(),
            Mapping::MATCHING_METHOD_STR_STARTS_WITH       => str_starts_with($this->solidUri->getUri(), $mapping->getRequestMatcher()->getUri()),
            Mapping::MATCHING_METHOD_STR_CONTAINS          => str_contains($this->solidUri->getUri(), $mapping->getRequestMatcher()->getUri()),
            Mapping::MATCHING_METHOD_REGEX                 => preg_match('~'.$mapping->getRequestMatcher()->getUri().'~', $this->solidUri->getUri()) === 1,
            default => false
        };
    }

    /**
     * @param string|SolidUri|null $uri the request uri (as SolidUri instance or simple string) to run the mapping logic against or null to use the current request uri
     * @return void
     * @throws \Exception
     */
    public function run (string|SolidUri|null $uri = null) : void {

        if(isset($this->callbacks['beforeRun']))
            ($this->callbacks['beforeRun'])($this);

        if($this->ran) // force more clean implementation of this class
            throw new \Exception('The RequestMapper instance has already been run. You can use the >update< method to re-run the RequestMapper-logic.');

        $this->ran = true;

        $uri ??= $_SERVER['REQUEST_URI'];

        $this->solidUri = $solidUri = $uri instanceof SolidUri ? $uri->getUri() : new SolidUri($uri);

        if(empty($this->mappings))
            $mappings = [Mapping::createDefault()];
        else
            $mappings = $this->mappings;;

        $defaultMappings = 0;
        $defaultMapping = null; // (this can never be more than one)

        if($this->respectPriorities) {
            $mappingsByPriority = [];
            foreach ($mappings as $i => $mapping) {
                /* @var $mapping Mapping */
                if($mapping->getPriority() === null)
                    throw new \Exception("Mapping at configuration-position >$i< does not have a priority set. Please make sure to set a priority for every mapping when the RequestMapper is configured to respect priorities.");

                while(array_key_exists($mapping->getPriority(), $mappingsByPriority)) {
                    if($mapping->getOnPriorityCollisionCallback() !== null) {
                        $newPriority = ($mapping->getOnPriorityCollisionCallback())(/* current iterated mapping */$mapping, /* mapping it collides with */$mappingsByPriority[$mapping->getPriority()], /* rm inst */ $this);
                        if(gettype($newPriority) !== 'integer')
                            throw new \Exception('The onPriorityCollision callback must return an integer that represents the new priority for the mapping.');
                        if($newPriority === $mapping->getPriority())
                            throw new \Exception('The onPriorityCollision callback must return a different priority than the current priority of the mapping. Make sure to return current mapping prio +=1 or -=1');
                        $mapping->setPriority($newPriority);
                    }
                    else
                        throw new \Exception('Multiple mappings with the same priority found. Please make sure to implement the the onPriorityCollision callback to move mappings up or down the priority chain as they collide.');
                }

                $mappingsByPriority[$mapping->getPriority()] = $mapping;
            }
            krsort($mappingsByPriority, SORT_NUMERIC);
            $mappings = $mappingsByPriority;
        }

        foreach ($mappings as $mappingIndex => $mapping) {
            /* @var $mapping Mapping */

            if(!$mapping instanceof Mapping)
                throw new \Exception('Invalid mapping parameter found. Please make sure to only pass instances of Mapping to the RequestMapper.');

            if($mapping->getOnTapCallback() !== null)
                ($mapping->getOnTapCallback())($mapping, $this);

            // Note: default mappings can't be skipped by returning false in the onMatch callback
            if($mapping->isDefaultMapping()) {
                $defaultMappings++;
                if($defaultMappings > 1)
                    throw new \Exception('Multiple default mappings found - please only pass one default mapping and use concrete mappings for the rest of the mappings. You can do so by using the createFor factory method instead of createDefault.');

                $defaultMapping = $mapping;

                $this->defaultMapping = &$defaultMapping;
                continue; // skip default mapping for the check because they need to checked with a lower priority then the concrete mappings
            }

            if($this->currentMapping === null /* < make sure the first matching mapping will be used, no matter if the default mapping has already been found or not */ && $this->requestMatches($mapping)) {
                $skipMapping = false;
                if($mapping->getOnMatchCallback() !== null) // allow the user to do some logic and perhaps decide to skip the match through his implementation of the onMatch callback (by returning false)
                    $skipMapping = ($mapping->getOnMatchCallback())($mapping, $this) === false;
                if(!$skipMapping)
                    $this->currentMapping = $mapping;
            }

            // break the loop after the default mapping and the first matching mapping is found
            if($this->currentMapping !== null && $defaultMapping !== null)
                break;
        }

        // if no specific mapping was found, fall back to the default mapping
        if($this->currentMapping === null)
            $this->currentMapping = $defaultMapping;

        if(!$this->currentMapping) // TODO remove? -> this can actually never happen because the class automatically instances a default mapping if no mapping is passed by the implementor
            throw new \Exception('No mapping found for the current request: ' . $uri. '. You also do not have a default mapping! Please make sure to always have a default mapping!');

        $this->currentMapping->setRequestMapper($this);

        // Destination file without an extension and yet without the base-path. Note that the dest-file may be not existing (checks following later)
        $str_SolidDestFilePath = ($this->requestIsMappingRequestBase() /* < eg.1: 4 req. '/' => true ; eg.2: Mapping::createFor('admin') -> 4 req. '/admin' => true ; 4 req. '/admin/<something>' => false */
            ? $this->getDefaultPagePathSolid() // returns the path to the default page file (without the actual page base) - so for example 'home'
            : $solidUri->getUri()
        );

        $this->applyStrip($str_SolidDestFilePath); // (method also ensures the path stays solid)

        $this->isDetailPageRequest = $this->isDetailPageEnabled() && str_contains($str_SolidDestFilePath, ('/' . $this->getDetailPageIdentifier() . '/'));

        $str_FullQualifiedPageFilePath = $this->getPageFilePath($str_SolidDestFilePath, $this->getPageBasePath());

        /** @var $obj_Page IPage instance of a class that implements IPage */
        if($str_FullQualifiedPageFilePath && is_file($str_FullQualifiedPageFilePath)) {
            $this->pageFileExists = true;
            if($this->isDetailPageRequest()) {
                $str_DetailPageClass = $this->getDetailPageClass();
                $str_SolidUri = $solidUri->getUri();
                $pretty_query = substr( $str_SolidUri, strpos($str_SolidUri, $this->getDetailPageIdentifier()) + strlen($this->getDetailPageIdentifier()) + 1 ); // the default query string can still be accessed via $_GET
                $obj_Page = new $str_DetailPageClass($this, $str_FullQualifiedPageFilePath, $pretty_query); /** @see DetailPage (default) */
            }
            else { // simple existing page
                $str_200PageClass = $this->get200PageClass();
                $obj_Page = new $str_200PageClass($this, $str_FullQualifiedPageFilePath); /** @see Page (default) */
            }
        }
        else { // page not found / 404
            $this->pageFileExists = false;
            $str_404PageClass = $this->get404PageClass(); /** @see Default404Page (default) */
            $obj_Page = new $str_404PageClass($this);
        }

        if($this->currentMapping->getOnPageInstantiationCompleteCallback() !== null)
            ($this->currentMapping->getOnPageInstantiationCompleteCallback())($obj_Page, $this->currentMapping, $this);

        if(!$obj_Page instanceof IPage)
            throw new \Exception('Page class does not implement IPage: ' . get_class($obj_Page) . ' - ' . $str_FullQualifiedPageFilePath);

        $this->page = $obj_Page;

        if(isset($this->callbacks['afterRun']))
            ($this->callbacks['afterRun'])($this, $obj_Page);

    }

    public function needsRedirect() : ?bool {
        $nr = $this->getRedirectUri();
        return $nr === null ? null : $nr !== false;
    }

    /**
     * @return string|false|null => string if we need a redirect; false if we don't need a redirect ; null if it does not matter because we will be running into a 404 what will never require a redirect
     */
    public function getRedirectUri(string $prefix = '') : string|bool|null {

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
            $str_RedirectPath = '/' . ltrim(rtrim($str_SolidUri, $this->getDefaultPagePathSolid()), '/');
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
        if($this->currentMapping->isDefaultMapping())
            return $this->solidUri->getUri() === $this->getDefaultPagePathSolid();

        if($this->currentMapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK || $this->currentMapping->getMatchingMethod() === Mapping::MATCHING_METHOD_STR_CONTAINS) // TODO second check is fuzzy
            return str_ends_with($this->solidUri->getUri(), $this->getDefaultPagePathSolid());
        else
            $requestBaselessUri = str_replace($this->getSolidRequestMatcherString() ?? '', '', $this->solidUri->getUri());
        return $requestBaselessUri === $this->getDefaultPagePathSolid() || $this->solidUri->getUri() === $this->getDefaultPagePathSolid();
    }

    /**
     * Returns true/false whether the current request is a detail page request or not or null if the dynamic detail page feature is disabled
     */
    public function isDetailPageRequest () : bool|null {
        return $this->isDetailPageRequest;
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
        return $this->pageFileExists;
    }

    public static function isReal404() : bool {
        $is_user_triggered_404 = strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false; // none user triggered: all requests that do not expect back a mime type of text/html
        return $is_user_triggered_404 === false;
    }

    /**
     * @param string|null $str_SolidUri if passed, this method assumes that it is a solid uri!
     * @return string|null returns the full qualified file path to the page file or null if the file does not exist
     */
    private function getPageFilePath(?string $str_SolidUri = null, ?string $str_SolidBasePath = null) : ?string {
        $str_SolidUri ??= $this->solidUri->getUri();
        $str_SolidBasePath ??= $this->getPageBasePath(); /** @see Mapping::getPageBasePath - it's a solid base path! */
        $str_PageFileExtension = $this->getPageFileExtension(); /** @see Mapping::getPageFileExtension - returns a trimed unified extension that always looks like ".<ext>" (eg. ".php" or ".blade.php"). E.g. "..<ext>" could never happen */

        if (is_file($f = sprintf('%s%s%s', $str_SolidBasePath, $str_SolidUri, $str_PageFileExtension))) {
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
            $file = sprintf('%s%s%s%s', $str_SolidBasePath, $page_file, $this->getDetailPageIdentifier(), $str_PageFileExtension);
        }
        else {
            /*
             * example: for resolving requests like this:
             * /foobar/test
             * to fs structure like this:
             * /pages/foobar/test/test.blade.php
             */
            $f = sprintf('%s%s/%s%s', $str_SolidBasePath, $str_SolidUri, basename($str_SolidUri), $str_PageFileExtension);
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
        if( $this->currentMapping->getMatchingMode() === Mapping::MATCHING_MODE_CUSTOM_CALLBACK || in_array($this->currentMapping->getMatchingMethod(), [Mapping::MATCHING_METHOD_STR_CONTAINS, Mapping::MATCHING_METHOD_REGEX]) ) {
            $uri = $this->solidUri->getUri();
            $this->applyStrip($uri);
            return $uri === '/';
        }
        return $this->requestMatches($this->currentMapping, Mapping::MATCHING_METHOD_EXACT);
    }

    /**
     * Just plain add a mapping - this method actually does the same as @see appendMapping but makes your code more readable/understandable if the RequestMapper is configured to respect priorities
     */
    public function addMapping (Mapping $mapping) : self {
        $this->mappings[] = $mapping;
        return $this;
    }

    /**
     * Alias for addMapping
     * Note that appending mappings actually only makes sense when the mapper is not configured to respect priorities
     */
    public function appendMapping (Mapping $mapping) : self {
        return $this->addMapping($mapping);
    }

    /**
     * Note that prepending mappings actually only makes sense when the mapper is not configured to respect priorities
     */
    public function prependMapping (Mapping $mapping) : self {
        array_unshift($this->mappings, $mapping);
        return $this;
    }

    // TODO add 'caching' as this method is called the first time... (create internal assoc array with str:reqBase => $mapping obj) so the next time this method is called we can spare some performance
    public function findMappingByRequestBase(string $requestBase) : ?Mapping {
        foreach ($this->mappings as $mapping) {
            /* @var $mapping Mapping */
            if($mapping->getMatchingMode() === Mapping::MATCHING_MODE_STR_METHODS && $mapping->getSolidRequestMatcherString() === (new SolidUri($requestBase))->getUri())
                return $mapping;
        }
        return null;
    }

    /**
     * Removes the configured string (fixed string or dynamic request base) from the passed file path while keeping the file path solid (the path passed is considered extension-less)
     *
     * This is necessary because the file path is built from the request uri - as soon as we have additional mapping with some special request base prefix, we need to strip this request base part off, otherwise reflecting the uri to the filesystem will fail.
     *
     * This method is automatically called by the RequestMapper class with the run & update method
     *
     * @param string &$destFile the path ref to the file the stripping should be applied to - note that this method will modify the passed string
     */
    private function applyStrip(string &$destFile) : void {
        if($this->currentMapping->isDefaultMapping() || $this->currentMapping->getStrip() === null)
            return;
        elseif (is_string($this->currentMapping->getStrip()))
            $part2Strip = $this->currentMapping->getStrip();
        elseif(is_callable($this->currentMapping->getStrip()))
            $part2Strip = ($this->currentMapping->getStrip())($destFile, $this->currentMapping, $this);
        elseif($this->currentMapping->getMatchingMethod() === Mapping::MATCHING_METHOD_STR_CONTAINS && $this->currentMapping->getStrip() === Mapping::STRIP_REQUEST_MATCHER_STRING) {
            $matches = [];
            preg_match('~\/(?<partialMatch>\w*'.trim($this->getSolidRequestMatcherString(), '/').'\w*)\/?~', $destFile, $matches);
            $part2Strip = $matches['partialMatch'] ?? '';
        }
        else
            $part2Strip = $this->getSolidRequestMatcherString();

        $destFile = (new SolidUri(str_replace($part2Strip, '', $destFile)))->getUri();
    }

    /*
     * redirects the request if needed - otherwise calls the implementer's closure to deliver the content
     */
    public function handle(\Closure $fn) : mixed {
        if(!$this->ran)
            throw new \Exception('The RequestMapper run method has not yet been invoked. Make sure to call the run method before trying to handle the request.');

        if(!RequestMapper::isReal404()) {
            $rm = $this;
            if($rm->needsRedirect()) {
                header('HTTP/1.0 308 Permanent Redirect');
                header('Location: ' . $rm->getRedirectUri());
                exit;
            } else {
                if($rm->getPage()->is404Page())
                    header('HTTP/1.0 404 Not Found');
                $page = $rm->getPage();
                return \Closure::bind($fn, $page)($page); // bind the custom closure to the page instance so implementers can use $this in their closure to reference the page instance
            }
        }
        return null;
    }

    public function ran() : bool {
        return $this->ran;
    }

    public function getCurrentMapping() : Mapping {
        return $this->currentMapping;
    }

    public function getDefaultMapping () : Mapping {
        return $this->defaultMapping;
    }
    public function overrideDefaultMapping (Mapping $mapping) : void {
        $this->defaultMapping = $mapping;
    }

    public function setRespectPriorities(bool $respect) : self {
        $this->respectPriorities = $respect;
        return $this;
    }
    public function respectsPriorities() : bool {
        return $this->respectPriorities;
    }

    /*
     * try to delegate most method calls to the current mapping instance, so we have those methods proxied directly on the RequestMapper instance
     */
    public function __call(string $methodName, array $arguments) {
        // TODO guess there are new methods @ Mapping class that are not yet listed here...
        if(in_array($methodName, [
            //'getSolidRequestBase',
            'getSolidRequestMatcherString',
            'isDefaultMapping',
            'setDefaultPagePath',
            'getDefaultPagePathSolid',
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
            'setRouteMap',
        ])) {
            return $this->currentMapping->$methodName(...$arguments);
        }
        throw new \Exception('Method does not exist on current mapping instance or is not allowed for delegation: ' . $methodName);
    }

    public static function getCurrentPage() : IPage {
        if(!self::$primaryInstance->ran())
            trigger_error('The RequestMapper has not been run yet. Make sure to call the run method before trying to access the current page.', E_USER_WARNING);
        return self::$primaryInstance->getPage();
    }

    public static function use(string $identifier) : RequestMapper {
        return self::$furtherInstances[$identifier];
    }

    public function makePrimary() : self {
        self::$primaryInstance = &$this;
        return $this;
    }

    public function identifyAs(string $identifier) : self {
        self::$furtherInstances[$identifier] = &$this;
        return $this;
    }

    public static bool $RAISE_EXCEPTION_ON_POINTLESS_CALLBACK_BINDING = true; // while there may be cases where it makes sense to bind callbacks after the RequestMapper instance has been run, in most cases it is pointless and may indicate a mistake in the implementation

    public function beforeRun(\Closure $fn) : self {
        if(self::$RAISE_EXCEPTION_ON_POINTLESS_CALLBACK_BINDING && $this->ran)
            throw new \Exception('Binding run callbacks is pointless if the RequestMapper instance has already been run');

        $this->callbacks['beforeRun'] = $fn;
        return $this;
    }
    public function afterRun(\Closure $fn) : self {
        if(self::$RAISE_EXCEPTION_ON_POINTLESS_CALLBACK_BINDING && $this->ran)
            throw new \Exception('Binding run callbacks is pointless if the RequestMapper instance has already been run');

        $this->callbacks['afterRun'] = $fn;
        return $this;
    }
    public function beforeUpdate(\Closure $fn) : self {
        $this->callbacks['beforeUpdate'] = $fn;
        return $this;
    }
    public function afterUpdate(\Closure $fn) : self {
        $this->callbacks['afterUpdate'] = $fn;
        return $this;
    }

}

/*
 * Last version of the mapper before adding different matching methods and before going away from just being able to use a simple request base:
 * https://github.com/serjoscha87/php-request-mapper/tree/07cec2f4dde83ca82a9d56bdfe7a4c0d3a7c2938
 * (just because at this point the class was much more simple, easier to understand and plain)
 */
