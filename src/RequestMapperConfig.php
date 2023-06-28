<?php

class RequestMapperConfig {

    private array $basePaths = []; // local base paths
    private ?BasePathConfig $defaultBasePath = null;

    //private ?IPage $fourOFourPage = null;
    /*
     * TODO die 404 page ist in die BasePathConfig gewandert damit requests die über einen anderen basepath aufgelöst werden auch eine eigene 404 seite haben können
     */

    private ?string $defaultPage = null;
    private ?string $pageFileExtension = null;

    private ?RequestMapperMultiLanguageConfig $mlConfig = null;

    private ?array $routeMap = [];

    // old removed param: IPage $fourOFourPage = null
    public function __construct(BasePathConfig $defaultBasePath, ?array /* @var $basePaths array of BasePathConfig */ $basePaths = null, string $pageFileExtension = null) {
        /*if(realpath($defaultBasePath->getBasePath()) === false) // if we can't identify the path as fully qualified, we must expect it is a relative path and therefor we need to strip guiding slashes
            $this->defaultBasePath = ltrim($defaultBasePath->getBasePath(), '/');
        else
            $this->defaultBasePath = $defaultBasePath->getBasePath();*/

        $this->defaultBasePath = $defaultBasePath; /* @var $defaultBasePath BasePathConfig */

        if(is_array($basePaths))
            $this->basePaths = $basePaths;

        //$this->fourOFourPage = $fourOFourPage ?? new Default404Page();
        //$this->fourOFourPage = $fourOFourPage ?? self::createDefault404Page();
        //$this->fourOFourPage = $fourOFourPage ?? (new RequestMapper('/404', RequestMapper::getGlobalConfig() ?? self::createDefaultConfig()))->getPage();
        //$this->fourOFourPage = $fourOFourPage ?? (new RequestMapper('/404', RequestMapper::getGlobalConfig() ?? $this))->getPage();

        if($pageFileExtension !== null)
            $this->setPageFileExtension($pageFileExtension);
    }

    /**
     * factory method that generates a default config
     * @return RequestMapperConfig
     */
    public static function createDefaultConfig () : RequestMapperConfig {
        return new self(new BasePathConfig('pages'), null, '.php');
        //return new self('pages', null, new class extends IPage {}, '.php');
        //return new self('pages', null, self::createDefault404Page(), '.php');
    }

    /*public static function createDefault404Page () : IPage {
        return (new RequestMapper('/404', null))->getPage();
    }*/

    /*public function setBasePaths(array $basePaths) {
        $this->basePaths = $basePaths;
    }*/
    /*
     * the local base path is only available for the object / instance its self
     */
    public function registerBasePath(string $url, BasePathConfig $config) : self {
        $this->basePaths[$url] = $config;
        return $this;
    }
    public function getBasePaths() : array /* ... of BasePathConfig */ {
        return $this->basePaths;
    }

    /**
     * @return BasePathConfig
     */
    public function getDefaultBasePath() : BasePathConfig {
        return $this->defaultBasePath;
    }

    /**
     * @param BasePathConfig $defaultBasePath
     */
    public function setDefaultBasePath(BasePathConfig $defaultBasePath) : void {
        $this->defaultBasePath = $defaultBasePath;
    }

    /**
     * @return Default404Page|IPage|null
     */
    public function get404Page() : Default404Page|IPage|null {
        return $this->fourOFourPage;
        //return $this->fourOFourPage ?? CurrentRequest::inst()->mapper()->getBasePathConfig()->getFourOFourPage() ?? null;
    }

    /**
     * @param IPage|null $fourOFourPage
     */
    public function set404Page(?IPage $fourOFourPage) : void {
        $this->fourOFourPage = $fourOFourPage;
    }

    private bool $enable_dynamic_detail_page = true;
    private bool $dynamic_detail_page_query_override_get = true;

    /**
     * @return bool
     */
    public function isDynamicDetailPageEnabled () : bool {
        return $this->enable_dynamic_detail_page;
    }

    /**
     * @param bool $enable_dynamic_detail_page
     */
    public function setDynamicDetailPageEnabled(bool $enable_dynamic_detail_page) : void {
        $this->enable_dynamic_detail_page = $enable_dynamic_detail_page;
    }

    /**
     * @return bool
     */
    public function doesDynamicDetailPageQueryOverrideGet() : bool {
        return $this->dynamic_detail_page_query_override_get;
    }

    /**
     * @param bool $dynamic_detail_page_override_get
     */
    public function setDynamicDetailPageQueryOverrideGet(bool $dynamic_detail_page_override_get) : void {
        $this->dynamic_detail_page_query_override_get = $dynamic_detail_page_override_get;
    }

    /**
     * @return string
     */
    public function getPageFileExtension() : string {
        return $this->pageFileExtension;
    }

    /**
     * @param string $pageFileExtension
     */
    public function setPageFileExtension(string $pageFileExtension) : void {
        $this->pageFileExtension = '.' . trim(ltrim($pageFileExtension, '.')); // make sure there is only (just one) dot at the start of the extension
    }

    /**
     * @return string|null
     */
    public function getDefaultPage() : ?string {
        return $this->defaultPage;
    }

    /**
     * set the name of the default "home" / start / entrance page file
     * @param string|null $defaultPage
     */
    public function setDefaultPage(?string $defaultPage) : void {
        $this->defaultPage = trim(str_replace($this->pageFileExtension, '', $defaultPage));
    }

    /**
     * @return array|null
     */
    public function getRouteMap() : ?array {
        return $this->routeMap;
    }

    /**
     * @param array|null $routeMap
     */
    public function setRouteMap(?array $routeMap) : void {
        $this->routeMap = $routeMap;
    }

    /**
     * @return RequestMapperMultiLanguageConfig|null
     */
    public function getMultiLanguageConfig() : ?RequestMapperMultiLanguageConfig {
        return $this->mlConfig;
    }

    /**
     * @param RequestMapperMultiLanguageConfig|null $mlConfig
     */
    public function setMultiLanguageConfig(?RequestMapperMultiLanguageConfig $mlConfig) : void {
        $this->mlConfig = $mlConfig;
    }

    /**
     * as soon as you set a multilang config the request mappers considers the instance to use multilang - keep the ml config to null in order to not use the ml feature
     * @return bool
     */
    public function usesMultiLanguage () : bool {
        return $this->mlConfig !== null;
    }

}
