<?php

require_once '../kint-new/kint.phar';

if(PHP_MAJOR_VERSION === 8) {
    function dd(...$v) {
        d(...$v);
        exit;
    }
}

set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, [
        'src', 'poly'
    ]));
spl_autoload_register(function($class_name) {
    $class_name = basename(str_replace('\\', '/', $class_name));
    if ($file = stream_resolve_include_path("${class_name}.php"))
        return require_once $file;
});

// --------------------------------------------------------------------------------------------------------------------

/*
 * TODO auch das hier ist angepasst worden - allerdings bin ich damit noch nicht so recht zufrieden
 */
// generate accurate filepath form the base string and the given file path
/*function filePath($fq_clean_path, $base = null, $extension = null) {
    $extension = $extension ?? \Config::BLADE_FILE_EXTENSION; // TODO perhaps use RequestMapperConfig->getBladeFileExtension()
    $base = $base ?? \Config::PAGES_DIR;
    if (is_file($f = sprintf('%s/%s%s', $base, $fq_clean_path, $extension))) // for access to files that have no dir that they are placed in
        $file = $f;
    elseif($f = sprintf('%s/%s/%s%s', $base, $fq_clean_path, basename($fq_clean_path), $extension))
        $file = $f;
    else
        $file = null;
    return RequestMapper::cleanUri($file);
}
*/
