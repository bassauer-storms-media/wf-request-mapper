<?php

/**
 * @noinspection PhpUnused
 */

namespace serjoscha87\phpRequestMapper;

/*
 * Page class implementation for EXISTING pages (only)
 */

class Page extends PageBase implements IPage {

    public array $customData = []; // for user defined data that for example, can be set in the onPageInstantiationComplete callback to add some additional information to the page object and which can then be addressed in the page-file itself

    public function __construct(RequestMapper $requestMapper, string $filePath) {
        /**
         * $this->requestMapper @see PageBase
         * $this->filePath @see PageBase
         */
        $this->requestMapper = $requestMapper;
        $this->filePath = $filePath;
    }

    public function __toString() : string {
        return $this->getName();
    }

    /**
     * @return string|null filename without extension and path (if the path is available)
     */
    public function getName() : string|null {
        return $this->filePath ? basename($this->filePath, $this->requestMapper->getPageFileExtension()) : null;
    }

    /**
     * @return string|null
     */
    public function getFilePath() : ?string {
        return $this->filePath;
    }

}
