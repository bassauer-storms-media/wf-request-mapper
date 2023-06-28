<?php

trait TRequestMapperBasePaths {

    /*private $defaultBasePath = null;
    public function setDefaultBasePath($path = null) {
        $this->defaultBasePath = $path;
    }
    public function getDefaultBasePath() {
        return $this->defaultBasePath;
    }*/


    /*
     * the global base path is available through ALL Page instances that may exist
     */
    public static $globalBasePaths = [];
    public static function registerGlobalBasePath($path, BasePathConfig $config) {
        self::$globalBasePaths[$path] = $config;
    }


    /*
     * the local base path is only available for the object / instance its self
     */
    /*private array $basePaths = [];
    public function registerBasePath(string $url, BasePathConfig $config) {
        $this->basePaths[$url] = $config;
    }*/


    /*private ?BasePathConfig $basePathConfig = null;
    public function setBasePathConfig(BasePathConfig $config) {
        $this->basePathConfig = $config;
    }*/

    //private ?array $basePathsCombined = null; // caching var
    //private function getBasePathsCombined () {
    /*
     * both combine basepaths into a local variable and the return this generated var
     */
    protected $basePathsCombined = null;
    private function getCombineBasePaths () : array {
        // we cannot cache, otherwise base paths are added afterwards will never be processed by applyBasePathStrip
        //if($this->basePathsCombined)
            //return $this->basePathsCombined;

        /* @var $this->config RequestMapperConfig */
        $this->basePathsCombined = array_merge(
            $this->getConfig()->getBasePaths(), // array of BasePathConfig
            self::$globalBasePaths
        );
        return $this->basePathsCombined;
    }

    /**
     * search through all configured base paths and apply(/strip) the 'strip'-string to the passed uri if a basepath was found for the passed uri
     * note that this does nothing at all if no basepath config was found for the current request
     *
     * This method is automatically called by the RequestMapper class with the run & update method
     *
     * @param $destFile
     * @return void
     */
    public function applyBasePathStrip(&$destFile) : void {
        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => /* @var $config BasePathConfig */ $config) {
            $url = ltrim($url, '/');
            if(str_contains($this->uri, $url)) {
                if($config->getStrip() === BasePathConfig::STRIP_REQUEST_BASE) {
                    $destFile = str_replace(
                        ($config->getStrip() === BasePathConfig::STRIP_REQUEST_BASE) ? $url : $config->getStrip(),
                        '',
                        $destFile
                    );
                }
            }
            //return $destFile;
        }
        //return null;
    }

    /**
     * get the registered base path (which were registered with Page::inst()->registerBasePath()...) or the default base-path if none of the registered base-paths matches for the current requested uri
     * @return string the base path for the current request uri
     */
    //public function getFileBaseDir() : BasePathConfig {
    //    if($this->uri === '')
    //        return $this->getConfig()->getDefaultBasePath();
    //    foreach($this->getCombineBasePaths() as /* @var $url string */ $url => $config /* @var $config BasePathConfig */) {
    //        $url = ltrim($url, '/');
    //        if(str_contains($this->uri, $url))
    //            return $config->getBasePath();
    //    }
    //    return $this->getConfig()->getDefaultBasePath();
    //}

    /*
     * TODO copy and paste from above -> improve this somehow
     */
    public function getBasePathConfig () : BasePathConfig|null {
        foreach($this->getCombineBasePaths() as /* @var $url string */ $url => $config /* @var $config BasePathConfig */) {
            $url = ltrim($url, '/');
            if(str_contains($this->uri, $url))
                return $config;
        }

        $config = $this->getConfig(); /* @var $config RequestMapperConfig */
        return $config->getDefaultBasePath();
    }

}
