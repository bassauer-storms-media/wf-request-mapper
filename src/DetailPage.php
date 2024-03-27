<?php

namespace serjoscha87\phpRequestMapper;

class DetailPage extends Page implements IPage {

    protected string $query = '';

    public function __construct(RequestMapper $request_mapper, $filePath, string $query = '') {
        $this->query = $query;
        parent::__construct($request_mapper, $filePath);
    }

    /**
     * @return ?string the name of the parent that contains the actual detail page file. Prefixed with "detail-"
     */
    public function getName() : string|null {
        return $this->filePath ? sprintf('detail-%s', basename($this->getBasePath())) : null;
    }

    public function getQuery() : string {
        return $this->query;
    }

    public function setQuery(string $query) : void {
        $this->query = $query;
    }

}
