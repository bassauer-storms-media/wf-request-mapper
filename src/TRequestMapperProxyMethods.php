<?php
 trait TRequestMapperProxyMethods {

     public function registerBasePath (string $url, BasePathConfig $config) {
         $this->getConfig()->registerBasePath($url, $config);
     }

 }
