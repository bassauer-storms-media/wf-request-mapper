<?php

namespace serjoscha87\phpRequestMapper;

/*
 * global class that allows access to the current page instance
 * NOTE THAT THIS WILL ALWAYS REFERENCE THE PRIMARY REQUESTMAPPER INSTANCE
 */

/**
 * Methods delegated through the 'magic method' __callStatic @see Page, PageBase
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

    private static function perhapsRaiseNoInstanceException() {
        if(!RequestMapper::$primaryInstance)
            throw new \Exception('No RequestMapper instance available. Make sure to instance a RequestMapper first.');
    }

    public static function get() : IPage {
        self::perhapsRaiseNoInstanceException();
        return RequestMapper::$primaryInstance->getPage();
    }

    public static function getRequestMapper () : RequestMapper {
        self::perhapsRaiseNoInstanceException();
        return RequestMapper::$primaryInstance;
    }

}
