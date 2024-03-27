<?php

namespace serjoscha87\phpRequestMapper;

class PageBase {

    protected ?RequestMapper $request_mapper = null;

    /**
     * Returns the path that contains the actual page file
     * @return ?string
     */
    public function getBasePath () : ?string {
        return pathinfo($this->getFilePath(), PATHINFO_DIRNAME);
    }

    public function isDefaultPage () : bool {
        /* @var $rm RequestMapper */
        $rm = $this->request_mapper;
        return $rm->isUriEmpty() && $rm->pageFileExists();
    }

    public function is404Page () : bool {
        return !$this->request_mapper->pageFileExists();
    }

    public function isDetailPage () : bool {
        //return get_class($this) === DetailPage::class;
        return $this->request_mapper->isDetailPageRequest();
    }

    /**
     * @return RequestMapper|null
     */
    public function getRequestMapper() : ?RequestMapper {
        return $this->request_mapper;
    }
    /**
     * Alias for @see getRequestMapper()
     * @return RequestMapper|null
     */
    public function mapper() : ?RequestMapper {
        return $this->getRequestMapper();
    }

}
