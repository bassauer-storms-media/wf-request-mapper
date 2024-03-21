<?php

namespace phpRequestMapper;

/*
 * this default class just returns false for all methods
 */

class Default404Page extends PageBase implements IPage {

    public function __toString() : string {
        return '404';
    }

    public function getName() : string {
        return '404';
    }

    public function getFilePath () : string {
        $rm = $this->getRequestMapper();
        return sprintf('%s/404%s', $rm->getBasePathConfig()->getBasePath(), $rm->getFileExtension());
    }

    // TODO untested
    public function getRequestMapper() : RequestMapper|null {
        return CurrentRequest::inst()->mapper(); //->overridePage($this);
    }

}
