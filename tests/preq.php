<?php

require_once '../../kint-new/kint.phar';

if(PHP_MAJOR_VERSION === 8) {
    function dd(...$v) {
        d(...$v);
        exit;
    }
}

set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, [
        '../src', '../poly'
    ]));
spl_autoload_register(function($class_name) {
    $class_name = basename(str_replace('\\', '/', $class_name));
    if ($file = stream_resolve_include_path("${class_name}.php"))
        return require_once $file;
});

// --------------------------------------------------------------------------------------------------------------------

function getAssertionResult ($condition) {
    return $condition ? 'assertion <b style="color: green">SUCCESSFUL</b>' : 'assertion <b style="color: red">FAILED</b>';
}
