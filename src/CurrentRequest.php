<?php

namespace serjoscha87\phpRequestMapper;

class CurrentRequest {

    protected static ?CurrentRequest $instance = null;

    private ?RequestMapper $request_mapper = null;

    public function __construct(?\Closure $beforeRun = null) {
        $this->request_mapper = new RequestMapper(uri: $_SERVER['REQUEST_URI'], beforeRun: ($beforeRun ?? function(RequestMapper $rm) {
            $rm->setInstancedBy(self::class /* => "CurrentRequest" */);
        }));
    }

    public static function inst(?\Closure $beforeRun = null) : ?CurrentRequest {
        if(!self::$instance)
            self::$instance = new self($beforeRun);
        return self::$instance;
    }

    public function override(RequestMapper $request_mapper) : void {
        $this->request_mapper = $request_mapper;
    }

    public function overridePage(IPage $page) : void {
        $this->request_mapper->overridePage($page);
    }

    /**
     * @return RequestMapper|null
     */
    public function getRequestMapper() : ?RequestMapper {
        return $this->request_mapper;
    }

    /**
     * alias for @see getRequestMapper()
     * @return RequestMapper|null
     */
    public function mapper() : ?RequestMapper {
        return $this->request_mapper;
    }

    /*
     * PROXY METHODS
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

    public static function isAjax() {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')) 
            ||
            (isset($_SERVER["CONTENT_TYPE"]) && stripos($_SERVER["CONTENT_TYPE"], 'application/json') !== false) 
            ||
            (isset($_SERVER["HTTP_CONTENT_TYPE"]) && stripos($_SERVER["HTTP_CONTENT_TYPE"], 'application/json') !== false)
        );
    }

}
