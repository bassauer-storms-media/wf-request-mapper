<?php

namespace phpRequestMapper;

trait TDefaultsValidators {

    /*
     * method is used from both classes: RequestMapperConfig & BasePathConfig @ setDefaultDefaultPage / setDefaultPage
     */
    private function getValidateDefaultPage(?string $page, ?string $extension = null) {
        if($extension)
            return  '/' . trim(ltrim(str_replace($extension, '', $page), '/'));
        else
            throw new Exception('when passing a default page you must also pass a default page file extension');
    }

    /**
     * Make sure the extension always starts with ONE dot - no matter if the source string had one or not
     * @param string $defaultPageFileExtension
     * @return string
     */
    private static function getUnifiedExtension(string $defaultPageFileExtension) : string {
        return '.' . trim(ltrim($defaultPageFileExtension, '.'));
    }

}
