<?php

namespace serjoscha87\phpRequestMapper;

class RequestMapperConfig {

    use TDefaultsValidators;

    private ?RequestMapper $requestMapper = null;

    private array $furtherBasePaths = []; // local base paths
    private ?BasePathConfig $defaultBasePathConfig = null;

    public string $defaultDefaultPage = 'home'; // nope, this ain't a typo. It is the default page if the BasePathConfig does not have a default page defined - so this is the default defaultPage fallback
    public string $defaultPageFileExtension = '.php'; 

    private ?RequestMapperMultiLanguageConfig $mlConfig = null;

    private ?array $routeMap = [];

    public function __construct(
        BasePathConfig $defaultBasePathConfig,
        
        ?array /** of @see BasePathConfig */ $furtherBasePaths = null,

        // BasePathConfig can also have the following attribs defined - if so this here is a fallback
        ?string $defaultDefaultPage = null,
        ?string $defaultPageFileExtension = null
    ) {
        
        $this->defaultBasePathConfig = $defaultBasePathConfig; /* @var $defaultBasePathConfig BasePathConfig */

        foreach ((array)$furtherBasePaths + [$defaultBasePathConfig] as $basePath)
            $basePath->setRequestMapperConfig($this);
        if($furtherBasePaths)
            $this->furtherBasePaths = $furtherBasePaths;

        if($defaultPageFileExtension !== null)
            $this->setDefaultPageFileExtension($defaultPageFileExtension); // its important this is set before the defaultDefaultPage is set
        if($defaultDefaultPage !== null)
            $this->setDefaultDefaultPage($defaultDefaultPage);
    }

    /**
     * factory method that generates a default config
     * @return RequestMapperConfig
     */
    public static function createDefaultConfig () : RequestMapperConfig {
        // we don't pass the [default page / extension] so the default config will use the private defs of this class ('home' / '.php')
        return new self(new BasePathConfig('pages', defaultPage: 'home', pageFileExtension: '.php'));
    }

    /*
     * the local base path is only available for the object / instance its self
     */
    public function registerBasePath(string $url, BasePathConfig $config) : self {
        $this->furtherBasePaths[$url] = $config;
        return $this;
    }
    public function getFurtherBasePaths() : array /* ... of BasePathConfig */ {
        return $this->furtherBasePaths;
    }

    /**
     * @return BasePathConfig
     */
    public function getDefaultBasePathConfig() : BasePathConfig {
        return $this->defaultBasePathConfig;
    }

    /**
     * @param BasePathConfig $defaultBasePath
     */
    public function setDefaultBasePathConfig(BasePathConfig $defaultBasePath) : void {
        $this->defaultBasePathConfig = $defaultBasePath;
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
    public function getDefaultPageFileExtension() : string {
        return $this->defaultPageFileExtension;
    }

    /**
     * @param string $defaultPageFileExtension
     */
    public function setDefaultPageFileExtension(string $defaultPageFileExtension) : void {
        $this->defaultPageFileExtension = self::getUnifiedExtension($defaultPageFileExtension);
    }

    /**
     * @return string|null
     */
    public function /*ain't a typo!*/ getDefaultDefaultPage() : ?string {
        return $this->defaultDefaultPage;
    }

    /**
     * set the name of the default "home" / start / entrance page file
     */
    public function /*ain't a typo!*/ setDefaultDefaultPage(?string $defaultPage) : void {
        $this->defaultDefaultPage = $this->getValidateDefaultPage($defaultPage, $this->defaultPageFileExtension);
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

    public function setRequestMapper(RequestMapper $requestMapper) : void {
        $this->requestMapper = $requestMapper;
    }

    public function getRequestMapper() : RequestMapper {
        return $this->requestMapper;
    }

}
