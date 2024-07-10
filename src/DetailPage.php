<?php

/**
 * @noinspection PhpUnused
 */

namespace serjoscha87\phpRequestMapper;

/**
 * @method getFilePath()
 *  inherited @see Page
 */
class DetailPage/*->Page->PageBase*/ extends Page /* @see PageBase (transitive inheritance)*/ implements IPage {

    protected string $query = '';

    public function __construct(RequestMapper $requestMapper, string $filePath, string $query = '') /** @see Page::__construct */ {
        $this->query = $query;
        parent::__construct($requestMapper, $filePath);
    }

    /**
     * @return ?string the name of the parent that contains the actual detail page file. Prefixed with "detail-"
     * @throws \Exception
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
