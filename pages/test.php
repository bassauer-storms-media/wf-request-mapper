
<h1>pages/test.php</h1>

<?php

use serjoscha87\phpRequestMapper\{CurrentPage, RequestMapper};

/* @var $this MyCustomPage */
/* @var $page MyCustomPage */
/* @var $rm RequestMapper */

d($page, $this);

if(method_exists($page, 'getSliderImages')) {
    d($page->getSliderImages());
}
if(method_exists($page, 'getRandomNumber')) {
    d(
        $page->getRandomNumber(),
        $this->getRandomNumber()
    );
}

d($page->getName());

d($page->getRequestMapper());

d(
    RequestMapper::getCurrentPage()
);

d(
    RequestMapper::getCurrentPage()->getName(),
    RequestMapper::getCurrentPage()->getBasePath(),
    RequestMapper::getCurrentPage()->getFilePath(),
    //
    //CurrentRequest::use($rm)->getRequestMapper()->getPage()->getName(),
    $rm->getPage()->getName()
);

if(defined('LANG')) {
    d(constant('LANG'));
}
else {
    d('no lang defined');
}

return;

d(
    CurrentPage::get(),
    CurrentPage::get()->getName(),
    CurrentPage::get()->getBasePath(),
    CurrentPage::get()->getFilePath(),
    //
    //CurrentRequest::use($rm)->getRequestMapper()->getPage()->getName(),
    $rm->getPage()->getName()
);
?>
