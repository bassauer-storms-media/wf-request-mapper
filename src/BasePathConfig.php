<?php

namespace serjoscha87\phpRequestMapper;

class BasePathConfig {

    use TDefaultsValidators;

    const STRIP_REQUEST_BASE = 0;

    private ?RequestMapperConfig $requestMapperConfig = null;

    private null|string|int $strip = null;
    private string $basePath = '';
    private ?string $fourOFourPageClass = null;

    /*
     * Note on $pageFileExtension & $defaultPage:
     * this can ALSO be defined when instancing a RequestMapperConfig instance, BUT the value defined here will be used with priority - the value defined in the RequestMapperConfig instance will be used as fallback/default
     */
    public ?string $defaultPage = null;
    public ?string $pageFileExtension = null;
    public ?string $detailPageIdentifier = null;

    private ?string $requestBase = null; // will be set by the request mapper when paths are processed

    public function __construct(
        string $basePath, // < this is expected to be a filesystem path (not a url/request-path)
        null|string|int/*<BasePathConfig::STRIP_REQUEST_BASE>*/ $strip = null, 
        ?string $fourOFourPageClass = null,
        ?string $defaultPage = null, // without extension (but if you pass it with ext. it will be automatically be removed)
        ?string $pageFileExtension = null,
        ?string $detailPageIdentifier = null
    ) {
        
        $this->basePath = $basePath;

        $this->set404PageClass($fourOFourPageClass);

        $this->strip = $strip;

        if($pageFileExtension !== null)
            $this->setPageFileExtension($pageFileExtension);
        if($defaultPage !== null)
            $this->setDefaultPage($defaultPage);
        if($detailPageIdentifier !== null)
            $this->setDetailPageIdentifier($detailPageIdentifier);
    }

    public function __toString() : string {
        return $this->basePath;
    }

    /**
     * @return null|string|int
     */
    public function getStrip() : null|string|int {
        return $this->strip;
    }

    /**
     * allow to set a string to be stripped from the basepath or a the 'STRIP_REQUEST_BASE' constant of this class in order to make [?the url being stirpped by that string?]
     * @param null|string|int $strip
     */
    public function setStrip(null|string|int $strip) : self {
        $this->strip = $strip;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath() : string {
        return $this->basePath;
    }

    /**
     * @return BasePathConfig
     */
    public function setBasePath(string $basePath) : self {
        $this->basePath = $basePath;
        return $this;
    }

    public function get404PageClass() : string {
        return $this->fourOFourPageClass;
    }

    /**
     * @param ?string $fourOFourPageClass the full qualified class name of the 404 page
     * @return BasePathConfig
     */
    public function set404PageClass(?string $fourOFourPageClass = null) : self {
        $this->fourOFourPageClass = $fourOFourPageClass ?? Default404Page::class;
        if(!class_exists($this->fourOFourPageClass))
            throw new \InvalidArgumentException('404 page class does not exist: ' . $this->fourOFourPageClass);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPageFileExtension() : ?string {
        return $this->pageFileExtension;
    }

    public function setPageFileExtension(?string $pageFileExtension) : void {
        $this->pageFileExtension = self::getUnifiedExtension($pageFileExtension);
    }

    public function getDefaultPage() : ?string {
        return $this->defaultPage;
    }

    public function setDefaultPage(?string $defaultPage) : void {
        $this->defaultPage = $this->getValidateDefaultPage($defaultPage, $this->pageFileExtension);
    }

    public function getDetailPageIdentifier() : ?string {
        return $this->detailPageIdentifier;
    }

    public function setDetailPageIdentifier(?string $dpi) : void {
        $this->detailPageIdentifier = self::getUnifiedDetailPageIdentifier($dpi);
    }

    public function getRequestMapperConfig() : ?RequestMapperConfig {
        return $this->requestMapperConfig;
    }

    public function setRequestMapperConfig(?RequestMapperConfig &$requestMapperConfig) : void {
        $this->requestMapperConfig = $requestMapperConfig;
    }

    public function getRequestBase() : ?string {
        return $this->requestBase;
    }
    public function setRequestBase(?string $requestBase) : void {
        $this->requestBase = $requestBase;
    }

}
