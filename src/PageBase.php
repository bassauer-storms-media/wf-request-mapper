<?php

/**
 * @noinspection PhpUnused
 */

namespace serjoscha87\phpRequestMapper;

class PageBase {

    protected ?RequestMapper $requestMapper = null;

    protected ?string $filePath = null;

    /**
     * Returns the path that contains the actual page file
     * @return ?string
     * @throws \Exception
     */
    public function getBasePath () : ?string {
        if(!method_exists($this, 'getFilePath'))
            throw new \Exception('Your Implementation of ' . get_class($this) . ' MUST implement the getFilePath() method!');
        return pathinfo($this->getFilePath(), PATHINFO_DIRNAME);
    }

    public function isDefaultPage () : bool {
        /* @var $rm RequestMapper */
        $rm = $this->requestMapper;
        return $rm->isUriEmpty() && $rm->pageFileExists();
    }

    public function is404Page () : bool {
        return !$this->requestMapper->pageFileExists();
    }

    public function isDetailPage () : bool {
        return $this->requestMapper->isDetailPageRequest();
    }

    /**
     * @return RequestMapper|null
     */
    public function getRequestMapper() : ?RequestMapper {
        return $this->requestMapper;
    }
    /**
     * Alias for @see getRequestMapper()
     * @return RequestMapper|null
     */
    public function mapper() : ?RequestMapper {
        return $this->getRequestMapper();
    }

    /**
     * @param string $filePath
     */
    public function setFilePath(string $filePath) : void {
        $this->filePath = $filePath;
    }

}
