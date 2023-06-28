<?php

class CurrentRequest {

    protected static ?CurrentRequest $instance = null;

    private ?RequestMapper $request_mapper = null;

    /*public function __construct(?RequestMapperConfig $config = null) {
        $this->request_mapper = new RequestMapper($_SERVER['REQUEST_URI'], $config);
    }*/

    public function __construct(?Closure $beforeRun = null) {
        $this->request_mapper = new RequestMapper(uri: $_SERVER['REQUEST_URI'], beforeRun: ($beforeRun ?? function(RequestMapper $rm) {
            $rm->setInstancedBy(self::class/* => "CurrentRequest" */);
        }));
    }

    /*public static function inst(?RequestMapperConfig $config = null) : ?CurrentRequest {
        if(!self::$instance)
            self::$instance = new self($config);
        return self::$instance;
    }*/
    public static function inst() : ?CurrentRequest {
        if(!self::$instance)
            self::$instance = new self();
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
     * @return RequestMapper|null
     */
    public function mapper() : ?RequestMapper {
        return $this->request_mapper;
    }

    public static function getPage() : IPage {
        return self::inst()->mapper()->getPage();
        //return CurrentPage::get();
    }

}
