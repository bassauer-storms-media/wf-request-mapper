<?php

/*
 * Page class implementation for EXISTING pages (only)
 */

class Page implements IPage {

    protected ?RequestMapper $request_mapper = null;
    protected ?RequestMapperConfig $request_mapper_config = null;

    protected ?string $filePath = null;
    //private ?SplFileInfo $filePath = null;

    public function __construct(RequestMapper $request_mapper, $filePath) {
        $this->request_mapper = $request_mapper;
        $this->request_mapper_config = $request_mapper->getConfig();
        //$this->filePath = new SplFileInfo($filePath);
        $this->filePath = $filePath;
    }

    public function __toString() : string {
        return $this->getName();
    }

    public function getName() : string|null {
        return $this->filePath ? basename($this->filePath, $this->request_mapper_config->getPageFileExtension()) : null;
    }

    /*public function isDetailPage() : bool|null {
        $dynamic_detail_page_enabled = $this->request_mapper_config->isDynamicDetailPageEnabled();
        return $dynamic_detail_page_enabled ? (str_contains($this->request_mapper->getUri(), '/detail/')) : null;
    }*/

    public function getUri () : string {
        return $this->request_mapper->getUri();
    }

    /**
     * @return string|null
     */
    public function getFilePath() : ?string {
        return $this->filePath;
    }

    /**
     * @param string|null $filePath
     */
    public function setFilePath(?string $filePath) : void {
        $this->filePath = $filePath;
    }

    /**
     * @return RequestMapper|null
     */
    public function getRequestMapper() : ?RequestMapper {
        return $this->request_mapper;
    }

}
