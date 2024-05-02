<?php

namespace serjoscha87\phpRequestMapper;

class CurrentRequest {

    protected static ?CurrentRequest $instance = null;

    public static function inst() : CurrentRequest {
        if(self::$instance === null)
            self::$instance = new self();
        return self::$instance;
    }

    private RequestMapper $request_mapper;

    /**
     * @throws \Exception
     */
    public function __construct() {
        $this->request_mapper = new RequestMapper(/*use global mapping config when run*/);
        $this->request_mapper->run();
    }

    public function override(RequestMapper $request_mapper) : self {
        $this->request_mapper = $request_mapper;
        return $this;
    }

    /*public function overridePage(IPage $page) : void {
        $this->request_mapper->overridePage($page);
    }*/

    public function getRequestMapper() : RequestMapper {
        return $this->request_mapper;
    }
    /**
     * alias for @see getRequestMapper()
     */
    public function mapper() : RequestMapper {
        return $this->request_mapper;
    }

    public static function isAjax() {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) 
            ||
            (isset($_SERVER["CONTENT_TYPE"]) && stripos($_SERVER["CONTENT_TYPE"], 'application/json') !== false) 
            ||
            (isset($_SERVER["HTTP_CONTENT_TYPE"]) && stripos($_SERVER["HTTP_CONTENT_TYPE"], 'application/json') !== false)
        );
    }

    public static function handle(\Closure $fn) : mixed {
        return self::inst()->request_mapper->handle($fn);
    }

    /*
     * If the current request is answered by a detail page, this method returns the part of the request uri after the detail identifier
     */
    public static function getDetailPageQuery () : string|false {
        if(!method_exists(self::getPage(), 'getQuery'))
            return false;
        return self::getPage()->getQuery();
    }

    /*
     * PROXY METHODS
     * TODO use __callStatic
     */
    public static function getPage() : IPage {
        return self::inst()->mapper()->getPage();
    }

    public static function needsRedirect() : ?bool {
        return self::inst()->mapper()->needsRedirect();
    }

    public static function getRedirectUri(string $prefix = '') : string|null {
        return self::inst()->mapper()->getRedirectUri($prefix);
    }

    public function getUri() : string {
        return self::inst()->mapper()->getUri();
    }

    public function isDetailPageRequest() : bool {
        return self::inst()->mapper()->isDetailPageRequest();
    }

    public function resultsIn404() : bool {
        return !self::inst()->mapper()->pageFileExists();
    }

}
