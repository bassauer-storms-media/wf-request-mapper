<?php

declare(strict_types=1);

namespace serjoscha87\phpRequestMapper;

// TODO rename 2 -> RequestMapConfig / Map? / Mapping? / MapConfig? / MappingConfig? RequestMapping?
class Mapping {

    const STRIP_REQUEST_BASE = 0;

    protected RequestMapper $requestMapper;

    protected string $_404PageClass                 = Default404Page::class;
    protected string $_200PageClass                 = Page::class;
    protected string $detailPageClass               = DetailPage::class;

    protected SolidUri $pageBasePath;               // default: 'pages'
    protected SolidUri $defaultPagePath;            // default: 'home'
    protected string $pageFileExtension             = '.php';

    protected string $detailPageIdentifier          = 'detail';
    protected bool $detailPageEnabled               = true;

    protected null|string|int $strip                = null; // TODO better name

    protected ?SolidUri $requestBase                = null;
    protected ?\Closure $customRequestBaseCheck     = null;
    private string $matchingMode;
    const MATCHING_MODE_REQUEST_BASE                = 'request_base'; // TODO hiermit weiter - besser diese consts verwenden statt dann ständig im requetsmapper 'is_callable' zu verwenden -> performance + code lesbarkeit
    const MATCHING_MODE_CUSTOM_CALLBACK             = 'custom_callback';

    protected ?\Closure $onMatchCallback           = null;

    protected array $routeMap                       = [];

    private function __construct(string|\Closure $requestBase) {
        if(is_string($requestBase)) {
            $this->requestBase = new SolidUri($requestBase);
            $this->matchingMode = self::MATCHING_MODE_REQUEST_BASE;
        }
        else {
            $this->customRequestBaseCheck = $requestBase;
            $this->matchingMode = self::MATCHING_MODE_CUSTOM_CALLBACK;
        }

        $this->setPageBasePath('pages');
        $this->setDefaultPagePath('home');
    }

    /**
     * @return self
     */
    public static function createDefault() : IDefaultMapping {
        return (new class('/') extends Mapping implements IDefaultMapping {
            protected ?bool $isDefaultMapping = true;

            // note: setRequestBase is not allowed for default mapping because it would make no sense
        });
    }

    /**
     * @return self
     */
    public static function createFor(string|\Closure $requestBase_or_customCheck) : IConcreteMapping|IDefaultMapping {
        if(is_string($requestBase_or_customCheck)) {
            $requestBase = trim($requestBase_or_customCheck, ' /');
            if($requestBase === '/' || $requestBase === '')
                return self::createDefault();
        }

        return new class($requestBase_or_customCheck) extends Mapping implements IConcreteMapping {
            protected ?bool $isDefaultMapping = false;

            public function setRequestBase(string|SolidUri $requestBase) : void {
                $this->requestBase = is_string($requestBase) ? new SolidUri($requestBase) : $requestBase;
                $this->matchingMode = self::MATCHING_MODE_REQUEST_BASE;
            }
        };
    }

    public function getMatchingMode() : string {
        return $this->matchingMode;
    }

    /**
     * @return ?string returns the request base path in as solid uri string or null when a custom request base check closure is used (because this callback just replaces the request base path)
     */
    public function getSolidRequestBase() : ?string {
        return $this->requestBase?->getUri() ?? null;
    }

    public function isDefaultMapping () : bool {
        return $this->isDefaultMapping; // < inherited from the dynamically created class
    }

    /*public function matches(SolidUri $solidUri, bool $exact = false) : bool {
        if($this->customRequestBaseCheck)
            return ($this->customRequestBaseCheck)($solidUri);
        return $exact ? $solidUri->getUri() === $this->getSolidRequestBase() : str_starts_with($solidUri->getUri(), $this->getSolidRequestBase());
    }*/

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
     * @return string return a solid uri string
     */
    public function getDefaultPagePath() : string {
        return $this->defaultPagePath->getUri();
    }

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
     * @param ?string $pageClass the full qualified class name of the page class - if null the default will be set
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

    public function setCustomRequestBaseCheck(\Closure $customRequestBaseCheck) : self {
        $this->customRequestBaseCheck = $customRequestBaseCheck;
        $this->matchingMode = self::MATCHING_MODE_CUSTOM_CALLBACK;
        return $this;
    }
    public function getCustomRequestBaseCheck() : ?\Closure {
        return $this->customRequestBaseCheck;
    }

    /**
     * allows to set a string to be stripped from the basepath or a the 'STRIP_REQUEST_BASE' constant of this class to make [?the url being stripped by that string?]
     * str -> strip off that custom string
     * null -> no not strip anything
     * 0 / STRIP_REQUEST_BASE -> strip the object-dependent request base string
     */
    public function setStrip(null|string|int $strip) : self {
        $this->strip = $strip;
        return $this;
    }
    public function getStrip() : null|string|int {
        return $this->strip;
    }

    public function setRouteMap(array $routeMap) : void {
        $this->routeMap = $routeMap;
    }
    public function getRouteMap() : ?array {
        return $this->routeMap;
    }

    public function onMatch(\Closure $onMatchCallback) : self {
        $this->onMatchCallback = $onMatchCallback;
        return $this;
    }
    public function getOnMatchCallback() : ?\Closure {
        return $this->onMatchCallback;
    }

    public function getRequestMapper() : RequestMapper {
        return $this->requestMapper;
    }
    public function setRequestMapper(RequestMapper &$requestMapper) : void {
        $this->requestMapper = $requestMapper;
    }

}
