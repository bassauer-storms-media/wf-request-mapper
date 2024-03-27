<?php

namespace serjoscha87\phpRequestMapper;

/*
 * global class that allows access to the current page request
 */

/**
 * Methods delegated through the 'magic method' __callStatic
 * @method static getName() : string|null
 * @method static getFilePath () : string|null;
 * @method static getBasePath () : string|null;
 * @method static is404Page () : bool;
 * @method static isDetailPage () : bool;
 * @method static isDefaultPage () : bool;
 */
class CurrentPage {

    /*
     * allows to "magically" access methods on the page object
     */
    public static function __callStatic(string $name, array $arguments) {
        return self::get()->$name(...$arguments);
    }

    public static function get(RequestMapperConfig $config = null) : IPage|DetailPage {
        /* @var $mapper RequestMapper */
        $mapper = (new CurrentRequest)->mapper();
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
    public static function mapper() : RequestMapper {
        return self::getRequestMapper();
    }

}
