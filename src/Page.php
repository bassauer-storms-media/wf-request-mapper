<?php

namespace phpRequestMapper;

/*
 * Page class implementation for EXISTING pages (only)
 */

class Page extends PageBase implements IPage {

    protected ?RequestMapper $request_mapper = null;
    protected ?RequestMapperConfig $request_mapper_config = null;

    protected ?string $filePath = null;

    public function __construct(RequestMapper $request_mapper, $filePath) {
        $this->request_mapper = $request_mapper;
        $this->request_mapper_config = $request_mapper->getConfig();
        $this->filePath = $filePath;
    }

    public function __toString() : string {
        return $this->getName();
    }

    /*
     * get filename without extension
     */
    public function getName() : string|null {
        return $this->filePath ? basename($this->filePath, $this->request_mapper->getFileExtension()) : null;
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
