<?php

namespace serjoscha87\phpRequestMapper;

class CurrentRequest {

    protected static ?CurrentRequest $instance = null;

    public static function inst() : ?CurrentRequest {
        if(!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }

    private ?RequestMapper $request_mapper = null;

    public function __construct() {
        $this->request_mapper = new RequestMapper($_SERVER['REQUEST_URI']);
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
    public function getRequestMapper() : RequestMapper {
        return $this->request_mapper;
    }

    /**
     * alias for @see getRequestMapper()
     * @return RequestMapper|null
     */
    public function mapper() : RequestMapper {
        return $this->request_mapper;
    }

    public function getConfig () : RequestMapperConfig {
        return $this->request_mapper->getConfig();
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

    /*
     * redirects the request if needed - otherwise calls your closure to deliver the content
     */
    public static function handle(\Closure $fn) : mixed {
        if(!RequestMapper::isReal404()) {
            $rm = self::inst()->mapper();
            if($rm->needsRedirect()) {
                header('HTTP/1.0 404 Not Found');
                header('Location: ' . $rm->getRedirectUri());
                exit;
            } else {
                return $fn($rm->getPage());
            }
        }
        return null;
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

    public function getUri() : string {
        return self::inst()->mapper()->getUri();
    }

    public function isDetailPageRequest() : bool {
        return self::inst()->mapper()->isDetailPageRequest();
    }

    public function resultsIn404() : bool {
        return !self::inst()->mapper()->pageFileExists();
    }

    /*
     * If the current request is answered by a detail page, this method returns the part of the request uri after the detail identifier
     */
    public static function getDetailPageQuery () : ?string {
        return self::inst()->mapper()->getPage()->getQuery();
    }

}
