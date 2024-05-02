<?php

namespace serjoscha87\phpRequestMapper;

/*
 * this default class just returns false for all methods
 */

class Default404Page extends PageBase implements IPage {

    public static string $fileName = '404'; // without extension / path

    /**
     * @throws \Exception
     */
    public function __construct(RequestMapper $request_mapper) {
        $this->request_mapper/*< inherited from PageBase */ = $request_mapper;

        if(!file_exists($this->getFilePath()))
            throw new \Exception('404 file not found: ' . $this->getFilePath());
    }

    public function __toString() : string {
        return self::$fileName;
    }

    public function getName() : string {
        return self::$fileName;
    }

    /**
     * @return string the path to the 404 file
     */
    public function getFilePath () : string {
        $rm = $this->getRequestMapper();
        return sprintf('%s/%s%s', $rm->getPageBasePath(), self::$fileName, $rm->getPageFileExtension());
    }

}
