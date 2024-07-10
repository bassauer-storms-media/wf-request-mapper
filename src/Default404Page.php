<?php

/**
 * @noinspection PhpUnused
 */

namespace serjoscha87\phpRequestMapper;

/*
 * this default class just returns false for all methods
 */

class Default404Page extends PageBase implements IPage {

    public static string $fileNameRegular         = '404'; // without extension / path
    public static string $fileNameDetail          = '404-detail'; // "

    public function __construct(RequestMapper $requestMapper) {
        /**
         * $this->requestMapper @see PageBase
         * $this->filePath @see PageBase
         */
        $this->requestMapper = $requestMapper;

        //if(!file_exists($this->getFilePath()))
            //throw new \Exception('404 file not found: ' . $this->getFilePath());
    }

    public function __toString() : string {
        return self::$fileNameRegular;
    }

    public function getName() : string {
        return self::$fileNameRegular;
    }

    /**
     * @return string the path to the 404 file
     */
    public function getFilePath () : string {

        // respect a possible filepath the implementer might have set through a callback
        if($this->filePath !== null)
            return $this->filePath;

        $rm = $this->getRequestMapper();

        $build404FilePath = fn(string $fileName) => sprintf('%s/%s%s', $rm->getPageBasePath(), $fileName, $rm->getPageFileExtension());

        $userRegular404File = false;
        if($rm->isDetailPageRequest()) {
            $pageRootDetail404File = $build404FilePath(self::$fileNameDetail);
            if(is_file($pageRootDetail404File))
                return $pageRootDetail404File;
            else
                $userRegular404File = true;
        }

        if(!$rm->isDetailPageRequest() || $userRegular404File)
            return $build404FilePath(self::$fileNameRegular);

    }

}
