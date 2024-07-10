<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace serjoscha87\phpRequestMapper;

class Mapping {

    protected RequestMapper $requestMapper;

    protected string $_404PageClass                                 = Default404Page::class;
    protected string $_200PageClass                                 = Page::class;
    protected string $detailPageClass                               = DetailPage::class;

    /** @var SolidUri contains the base path where all pages files and further subdirectories are located */
    protected SolidUri $pageBasePath;                               // by default set to 'pages'
    /**  @var SolidUri contains the default page path (without the actual page base path) as solid uri string - the default page is the pages delivered when no specific page is requested */
    protected SolidUri $defaultPagePath;                            // by default set to 'home'
    protected string $pageFileExtension                             = '.php';

    protected string $detailPageIdentifier                          = 'detail';
    protected bool $detailPageEnabled                               = true;

    protected ?int $priority                                        = null; // only relevant if the RequestMapper is configured to respect priorities

    //protected ?SolidUri $requestMatcherString                       = null; // previously  "requestBase"
    //protected ?\Closure $customRequestMatcherCallback               = null;
    protected null|\Closure|SolidUri $requestMatcher                  = null; // will be null for the defaultMapping

    //const STRIP_REQUEST_BASE                                        = 0; // default value
    const STRIP_REQUEST_MATCHER_STRING                              = 1; // default value
    const STRIP_NOTHING                                             = null;
    protected null|string|int|\Closure $strip                       = self::STRIP_NOTHING;

    protected ?int $matchingMode                                    = null; // will be null for the defaultMapping
    const MATCHING_MODE_STR_METHODS                                 = 1;
    const MATCHING_MODE_CUSTOM_CALLBACK                             = 2;

    private ?int $matchingMethod                                    = null; // only relevant if the matching mode is set to STR_METHODS
    const MATCHING_METHOD_EXACT                                     = 1;
    const MATCHING_METHOD_STR_STARTS_WITH                           = 2; // default value (only if MODE is not CUSTOM_CALLBACK)
    const MATCHING_METHOD_STR_CONTAINS                              = 4;
    const MATCHING_METHOD_REGEX                                     = 8;

    protected ?\Closure $onMatchCallback                            = null;
    protected ?\Closure $onTapCallback                              = null;
    protected ?\Closure $onPageInstantiationCompleteCallback        = null;
    protected ?\Closure $onPriorityCollisionCallback                = null;

    protected array $routeMap                                       = [];

    protected bool $isDefaultMapping;

    // class internal callbacks for better code readability
    //private ?\Closure $_onMatchingModeChange                        = null;

    private function __construct(string|\Closure $requestMatcher, ?int $matchingMethod = null) {
        if(is_string($requestMatcher)) {
            //$this->requestBase = new SolidUri($requestBase);

                        //$this->matchingMode = self::MATCHING_MODE_STR_METHODS;
            //$this->setMatchingMode(self::MATCHING_MODE_STR_METHODS);

            //$_matchingMethod = $matchingMethod ?? self::REQUEST_BASE_MATCHING_METHOD_STR_STARTS_WITH
            //$this->setMatchingMethod($_matchingMethod);
            $this->setMatchingMethod($matchingMethod ?? self::MATCHING_METHOD_STR_STARTS_WITH);

            /*if($_matchingMethod === self::REQUEST_BASE_MATCHING_METHOD_STR_STARTS_WITH)
                $this->requestMatcher = new SolidUri($requestMatcher);
            else
                $this->requestMatcher = new SolidUri($requestMatcher, '');*/

            // this callback is actually just for better code comprehension
            /*$this->_onMatchingModeChange = function(string $newMatchingMode) use ($requestBase) {
                //dd('this', $this);
                //if($newMatchingMode === self::MATCHING_MODE_STR_METHODS) {
                    $this->requestBase = new SolidUri(
                        $requestBase,
                        $newMatchingMode == self::REQUEST_BASE_MATCHING_METHOD_STR_CONTAINS ? '' : '/'
                    );
                //}
            };*/

            $this->setStrip(self::STRIP_REQUEST_MATCHER_STRING);
        }
        else {
            //$this->customRequestMatcherCallback = $requestMatcher;
            //$this->requestMatcher = $requestMatcher;
                    //$this->matchingMode = self::MATCHING_MODE_CUSTOM_CALLBACK;
            //$this->setMatchingMode(self::MATCHING_MODE_CUSTOM_CALLBACK);

            //$this->_onMatchingModeChange = fn() => null;
        }

        if(!$this->isDefaultMapping)
            $this->setRequestMatcher($requestMatcher);

        $this->setPageBasePath('pages');
        $this->setDefaultPagePath('home');
    }

    /**
     * @return self
     */
    public static function createDefault() : IDefaultMapping {
        return (new class('/') extends Mapping implements IDefaultMapping {
            protected bool $isDefaultMapping = true;
            protected ?int $priority = -1;

            /*public function __construct () {
                $this->isDefaultMapping = true;
                $this->priority = -1;

                $this->__construct();
            }*/

            // note: setRequestBase is not allowed for default mapping because it would make no sense
        });
    }

    /**
     * @see RequestMapper::requestMatches() for parameters passed to the closure passed to this method
     * @return self
     */
    public static function createFor(string|\Closure $requestMatcher, ?int $matchingMethod = null) : IConcreteMapping|IDefaultMapping {

        if(is_string($requestMatcher)) {
            if(trim($requestMatcher, '/') === '')
                return self::createDefault();
        }

        return new class($requestMatcher, $matchingMethod) extends Mapping implements IConcreteMapping {

            protected bool $isDefaultMapping = false;

            /*public function __construct ($requestMatcher, $matchingMethod) {
                $this->isDefaultMapping = true;

                $this->__construct($requestMatcher, $matchingMethod);
            }*/

            public function setRequestMatcher(string|SolidUri|\Closure $requestMatcher) : self {
                /*$this->requestMatcherString = is_string($requestBase) ? new SolidUri($requestBase) : $requestBase;
                //$this->matchingMode = self::MATCHING_MODE_REQUEST_BASE;
                return $this;*/

                if($requestMatcher instanceof \Closure || $requestMatcher instanceof SolidUri)
                    $this->requestMatcher = $requestMatcher;
                elseif($this->getMatchingMethod() === self::MATCHING_METHOD_STR_STARTS_WITH)
                    $this->requestMatcher = new SolidUri($requestMatcher); // make the solid uri always start with a slash
                else
                    $this->requestMatcher = new SolidUri($requestMatcher, ''); // make the solid uri NOT start with a slash

                if($requestMatcher instanceof \Closure)
                    $this->matchingMode = self::MATCHING_MODE_CUSTOM_CALLBACK;
                else
                    $this->matchingMode = self::MATCHING_MODE_STR_METHODS;

                return $this;
            }

            public function setPriority(?int $priority) : self {
                $this->priority = $priority;
                return $this;
            }
        };
    }

    /*🔒*//*private function setMatchingMode(string $matchingMode) : void {
        if($this->matchingMode && $this->matchingMode === $matchingMode)
            return;

        $this->matchingMode = $matchingMode;
        if(is_callable($this->_onMatchingModeChange))
            $this->_onMatchingModeChange($matchingMode);
    }*/
    public function getMatchingMode() : ?int {
        return $this->matchingMode;
    }

    /**
     * @return ?string returns the request base path in as solid uri string or null when a custom request base check closure is used (because this callback just replaces the request base path)
     */
    /*public function getSolidRequestBase() : ?string {
        return $this->requestMatcherString?->getUri() ?? null;
    }
    public function getRequestMatcherString() : ?SolidUri {
        return $this->requestMatcherString ?? null;
    }*/
    public function getRequestMatcher() : null|\Closure|SolidUri {
        return $this->requestMatcher;
    }
    public function getSolidRequestMatcherString() : ?string {
        // Note for default mappings matchingMode is null causing this method to also return null
        if($this->matchingMode === self::MATCHING_MODE_STR_METHODS) //  && !$this->isDefaultMapping()
            return $this->requestMatcher->getUri();
        return null;
    }

    public function isDefaultMapping () : bool {
        return $this->isDefaultMapping; // < inherited from the dynamically created class
    }

    /**
     * sets the default page path while making sure it does not contain the configured page file extension and is a solid uri
     */
    public function setDefaultPagePath(string|SolidUri $defaultPagePath) : self {
        if($defaultPagePath instanceof SolidUri)
            $defaultPagePath = $defaultPagePath->getUri();
        $this->defaultPagePath = new SolidUri(str_replace($this->pageFileExtension, '', $defaultPagePath));
        return $this;
    }
    /**
     * @return string returns the path to the default page file (without the actual page base) as solid uri string
     */
    public function getDefaultPagePathSolid() : string {
        return $this->defaultPagePath->getUri();
    }

    /**
     * set the base-path wile making sure it is a solid uri
     */
    public function setPageBasePath(string|SolidUri $pageBasePath) : self {
        if($pageBasePath instanceof SolidUri)
            $pageBasePath = $pageBasePath->getUri();
        $this->pageBasePath = new SolidUri($pageBasePath, '');
        return $this;
    }
    /**
     * @return string return a solid uri string
     */
    public function getPageBasePath() : string {
        return $this->pageBasePath->getUri();
    }

    /**
     * @param ?string $fourOFourPageClass the full qualified class name of the 404 page - if null the default will be set
     */
    public function set404PageClass(?string $fourOFourPageClass = null) : self {
        //if($fourOFourPageClass !== null && !$fourOFourPageClass instanceof IPage::class)
        //throw new \InvalidArgumentException('404 page class must implement IPage: ' . $fourOFourPageClass);
        $this->_404PageClass = $fourOFourPageClass ?? Default404Page::class;
        if(!class_exists($this->_404PageClass))
            throw new \InvalidArgumentException('404 page class does not exist: ' . $this->_404PageClass);
        return $this;
    }
    public function get404PageClass() : string {
        return $this->_404PageClass;
    }

    /**
     * @param ?string $pageClass the full qualified class name of the page class - if null, the default will be set
     */
    public function set200PageClass(?string $pageClass = null) : self {
        //if($pageClass !== null && !$pageClass instanceof IPage::class)
        //throw new \InvalidArgumentException('Page class must implement IPage: ' . $pageClass);
        $this->_200PageClass = $pageClass ?? Page::class;
        if(!class_exists($this->_200PageClass))
            throw new \InvalidArgumentException('Page class does not exist: ' . $this->_200PageClass);
        return $this;
    }
    public function get200PageClass() : string {
        return $this->_200PageClass;
    }

    public function setPageFileExtension(?string $pageFileExtension) : self {
        $this->pageFileExtension = '.' . trim(ltrim($pageFileExtension, '.'));
        return $this;
    }
    public function getPageFileExtension() : string {
        return $this->pageFileExtension;
    }

    public function setDetailPageEnabled(bool $dpe) : self {
        $this->detailPageEnabled = $dpe;
        return $this;
    }
    public function isDetailPageEnabled() : bool {
        return $this->detailPageEnabled;
    }

    /**
     * @param ?string $detailPageClass the full qualified class name of the detail page class - if null the default will be set
     */
    public function setDetailPageClass(?string $detailPageClass = null) : self {
        //if($detailPageClass !== null && !$detailPageClass instanceof IPage::class)
        //throw new \InvalidArgumentException('Detail page class must implement IPage: ' . $detailPageClass);
        $this->detailPageClass = $detailPageClass ?? DetailPage::class;
        if(!class_exists($this->detailPageClass))
            throw new \InvalidArgumentException('Detail page class does not exist: ' . $this->detailPageClass);
        return $this;
    }
    public function getDetailPageClass() : string {
        return $this->detailPageClass;
    }

    public function setDetailPageIdentifier(string $dpi) : self {
        $this->detailPageIdentifier = trim($dpi, '/');
        return $this;
    }
    public function getDetailPageIdentifier() : string {
        return $this->detailPageIdentifier;
    }

    /*public function setCustomRequestMatcherCallback(\Closure $customRequestMatcherCallback) : self {
        $this->customRequestMatcherCallback = $customRequestMatcherCallback;
        //$this->matchingMode = self::MATCHING_MODE_CUSTOM_CALLBACK;
        $this->setMatchingMode(self::MATCHING_MODE_CUSTOM_CALLBACK);
        return $this;
    }
    public function getCustomRequestMatcherCallback() : ?\Closure {
        return $this->customRequestMatcherCallback;
    }*/

    /**
     * allows to set a string to be stripped from the basepath or a the 'STRIP_REQUEST_BASE' constant of this class to make [?the url being stripped by that string?]
     * str -> strip off that custom string
     * null / @see self::STRIP_NOTHING -> do not strip anything
     * 0 / @see self::STRIP_REQUEST_BASE -> strip the object-dependent request base string
     */
    public function setStrip(null|string|int|\Closure $strip) : self {
        $this->strip = $strip;
        return $this;
    }
    public function getStrip() : null|string|int|\Closure {
        return $this->strip;
    }

    public function setRouteMap(array $routeMap) : void {
        $this->routeMap = $routeMap;
    }
    public function getRouteMap() : ?array {
        return $this->routeMap;
    }

    // set priority is only available for concrete mappings
    public function getPriority() : ?int {
        return $this->priority;
    }

    public function onMatch(\Closure $onMatchCallback) : self {
        $this->onMatchCallback = $onMatchCallback;
        return $this;
    }
    public function getOnMatchCallback() : ?\Closure {
        return $this->onMatchCallback;
    }

    /**
     * set a callback that is addressed when the route gets processed / "touched"
     * @param \Closure $onTapCallback
     * @return $this
     */
    public function onTap(\Closure $onTapCallback) : self {
        $this->onTapCallback = $onTapCallback;
        return $this;
    }
    public function getOnTapCallback() : ?\Closure {
        return $this->onTapCallback;
    }

    public function onPageInstantiationComplete(\Closure $onPageInstantiationCompleteCallback) : self {
        $this->onPageInstantiationCompleteCallback = $onPageInstantiationCompleteCallback;
        return $this;
    }
    public function getOnPageInstantiationCompleteCallback() : ?\Closure {
        return $this->onPageInstantiationCompleteCallback;
    }

    public function onPriorityCollision(\Closure $onPriorityCollisionCallback) : self {
        $this->onPriorityCollisionCallback = $onPriorityCollisionCallback;
        return $this;
    }
    public function getOnPriorityCollisionCallback() : ?\Closure {
        return $this->onPriorityCollisionCallback;
    }

    /**
     * This method is called by the RequestMapper as it iterates all mapping
     * This method is actually not meant to be called manually
     */
    public function setRequestMapper(RequestMapper &$requestMapper) : void {
        $this->requestMapper = $requestMapper;

        /*if(is_string($this->requestBaseOrig)) {
            $this->requestBase = new SolidUri(
                $this->requestBaseOrig,
                $this->getMatchingMode() == self::REQUEST_BASE_MATCHING_METHOD_STR_CONTAINS ? '' : '/'
            );
        }*/
    }
    public function getRequestMapper() : RequestMapper {
        return $this->requestMapper;
    }

    public function setMatchingMethod(int $matchingMethod) : self {
        if(!in_array($matchingMethod, [
            self::MATCHING_METHOD_EXACT,
            self::MATCHING_METHOD_STR_STARTS_WITH,
            self::MATCHING_METHOD_STR_CONTAINS,
            self::MATCHING_METHOD_REGEX
        ]))
            throw new \InvalidArgumentException('Invalid matching method: ' . $matchingMethod);

        $this->matchingMethod = $matchingMethod;
        return $this;
    }
    public function getMatchingMethod() : ?int {
        return $this->matchingMethod;
    }

}
