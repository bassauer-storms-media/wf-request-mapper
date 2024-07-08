bla.php <

<?php

use serjoscha87\phpRequestMapper\CurrentPage;

/* @var $page MyCustomPage */
$page = CurrentPage::get();

if(method_exists($page, 'getSliderImages')) {
    d($page->getSliderImages());
}

d(
    CurrentPage::get(),
    CurrentPage::get()->getName(),
    CurrentPage::get()->getBasePath(),
    CurrentPage::get()->getFilePath(),
);

if(defined('LANG')) {
    d(constant('LANG'));
}
?>
