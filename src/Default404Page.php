<?php

/*
 * this default class just returns false for all methods
 */

class Default404Page implements IPage {

    /*public function __construct() {

    }*/

    /*public function isDetailPage() : bool|null {
        return false;
    }*/

    public function __toString() : string {
        return '404';
    }

    public function getName() : string {
        return '404';
    }

    public function getFilePath () : string {
        $rmConfig = $this->getRequestMapper()->getConfig();
        return sprintf('%s/404%s', $rmConfig->getDefaultBasePath(), $rmConfig->getPageFileExtension());
    }

    public function getUri() : string {
        return $this->getRequestMapper()->getUri();
    }

    public function getRequestMapper () : RequestMapper {
        // TODO das kann theoretisch endlose rekursionen auslösen
        return new RequestMapper('/404', RequestMapper::getGlobalConfig() ?? RequestMapperConfig::createDefaultConfig(), function(RequestMapper $rm) {
            $rm->setInstancedBy(self::class);
        });
    }

}
