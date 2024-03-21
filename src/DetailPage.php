<?php

namespace phpRequetsMapper;

class DetailPage extends Page implements IPage {

    protected array $query = [];

    /**
     * @throws \http\Exception\BadUrlException
     */
    public function __construct(RequestMapper $request_mapper, $filePath, array $query = []) {
        $this->query = $query;
        parent::__construct($request_mapper, $filePath);
    }

    public function getName() : string|null {
        return $this->filePath ? sprintf('detail-%s', basename(pathinfo($this->filePath, PATHINFO_DIRNAME))) : null;
    }

    public function getQuery() {
        return $this->query;
    }

    public function setQuery(array $query) {
        $this->query = $query;
    }

}
