<?php

namespace phpRequetsMapper;

class PageBase {

    /**
     * Returns the path that contains the actual page file
     * @return ?string
     */
    public function getBasePath () : ?string {
        return pathinfo($this->getFilePath(), PATHINFO_DIRNAME);
    }

    public function getUri() : string {
        return $this->getRequestMapper()->getUri();
    }

    public function isDefaultPage () {
        /* @var $rm RequestMapper */
        $rm = $this->request_mapper;
        return $rm->isUriEmpty() && $rm->pageFileExists();
    }

    public function is404 () : bool {
        /* @var $rm RequestMapper */
        $rm = $this->request_mapper;
        return !$rm->pageFileExists();
    }

    public function isDetailPage () : bool {
        return get_class($this) === DetailPage::class;
    }

}
