<?php

/*
 * global class that allows access to the current page request like before through Page:: ...
 */

class CurrentPage {

    /*
     * allows to "magically" access methods on the page object
     */
    public function __call(string $name, array $arguments) {
        //return CurrentRequest::inst()->mapper()->getPage()->$name(...$arguments);
        return self::get()->$name(...$arguments);
    }

    public static function get(RequestMapperConfig $config = null) : IPage {
        /* @var $mapper RequestMapper */
        $mapper = (new CurrentRequest(function(RequestMapper $rm) {
            $rm->setInstancedBy(self::class/* => "CurrentPage" */);
        }))->mapper();
        if($config)
            $mapper->setConfig($config);
        return $mapper->getPage();
    }

    public static function override(IPage $page) : void {
        CurrentRequest::inst()->mapper()->overridePage($page);
    }

    public static function getRequestMapper () : RequestMapper {
        return CurrentRequest::inst()->mapper();
    }

}
