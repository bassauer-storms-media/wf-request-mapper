<?php

namespace serjoscha87\phpRequestMapper;

class BasePathConfig {

    use TDefaultsValidators;

    const STRIP_REQUEST_BASE = 0;

    private ?RequestMapperConfig $requestMapperConfig = null;

    private null|string|int $strip = null;
    private string $basePath = '';
    private ?IPage $fourOFourPage = null;

    /*
     * Note on $pageFileExtension & $defaultPage:
     * this can ALSO be defined when instancing a RequestMapperConfig instance, BUT the value defined here will be used with priority - the value defined in the RequestMapperConfig instance will be used as fallback
     */
    public ?string $pageFileExtension = null; 
    public ?string $defaultPage = null;

    public function __construct(
        string $basePath, // < this is expected to be a filesystem path (not a url/request-path)
        null|string|int/*<BasePathConfig::STRIP_REQUEST_BASE>*/ $strip = null, 
        ?IPage $fourOFourPage = null,
        ?string $defaultPage = null, // without extension (but if you pass it with ext. it will be automatically be removed)
        ?string $pageFileExtension = null
    ) {
        
        $this->basePath = $basePath;

        /* @var $fourOFourPage IPage */
        $this->fourOFourPage = $fourOFourPage ?? new Default404Page();

        $this->strip = $strip;

        if($pageFileExtension !== null)
            $this->setPageFileExtension($pageFileExtension);

        if($defaultPage !== null)
            $this->setDefaultPage($defaultPage);
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
     * @param string $base
     */
    public function setBasePath(string $basePath) : self {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return IPage|null
     */
    public function getFourOFourPage() : ?IPage {
        return $this->fourOFourPage;
    }

    /**
     * @param IPage|null $fourOFourPage
     */
    public function setFourOFourPage(?IPage $fourOFourPage) : void {
        $this->fourOFourPage = $fourOFourPage;
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

    public function getRequestMapperConfig() : ?RequestMapperConfig {
        return $this->requestMapperConfig;
    }

    public function setRequestMapperConfig(?RequestMapperConfig &$requestMapperConfig) : void {
        $this->requestMapperConfig = $requestMapperConfig;
    }

}
