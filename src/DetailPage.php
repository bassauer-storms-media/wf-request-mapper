<?php

class DetailPage extends Page implements IPage {

    //private ?string $query = null;

    /**
     * @throws \http\Exception\BadUrlException
     */
    public function __construct(RequestMapper $request_mapper, $filePath) {
        parent::__construct($request_mapper, $filePath);
        //$this->fromRequest();
    }

    /**
     * @throws \http\Exception\BadUrlException
     */
    /*public function fromRequest ($override_get = true) : void {
        if($this->request_mapper->isDetailPageRequest()) {
            $page_and_query = [];
            preg_match('/^(?<page_file>.*\/detail)\/(?<query>.*)$/', $this->request_mapper->getUri(), $page_and_query);
            $page_base = ltrim($this->request_mapper->getFileBaseDir(), '/');
            $this->filePath = filePath($page_and_query['page_file'], $page_base);
            $this->query = $page_and_query['query'];
            if($override_get)
                $_GET['query'] = $page_and_query['query'];
        }
        else
            throw new \http\Exception\BadUrlException('not a detail page request');
    }*/

    /*public function getQuery() : ?string {
        return $this->query;
    }*/

    public function getName() : string|null {
        return $this->filePath ? sprintf('detail-%s', basename(pathinfo($this->filePath, PATHINFO_DIRNAME))) : null;
    }

}
