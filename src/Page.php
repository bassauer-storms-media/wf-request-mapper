<?php

namespace serjoscha87\phpRequestMapper;

/*
 * Page class implementation for EXISTING pages (only)
 */

class Page extends PageBase implements IPage {

    protected ?string $filePath = null;

    public function __construct(RequestMapper $request_mapper, $filePath) {
        $this->request_mapper/*< inherited from PageBase */ = $request_mapper;
        $this->filePath = $filePath;
    }

    public function __toString() : string {
        return $this->getName();
    }

    /**
     * @return string|null filename without extension and path (if the path is available)
     */
    public function getName() : string|null {
        return $this->filePath ? basename($this->filePath, $this->request_mapper->getPageFileExtension()) : null;
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

}
