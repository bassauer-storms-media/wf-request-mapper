 
home.php <

<?php

use serjoscha87\phpRequestMapper\RequestMapper;
use serjoscha87\phpRequestMapper\CurrentPage;

/* @var $page MyCustomPage (injected by the RequestMapper it self) */

d('this', $this, $this->getName(), $page);

d(
    RequestMapper::$primaryInstance->getPage()->getName(),
    RequestMapper::$primaryInstance->getPage()->getBasePath(),
);

d(
    CurrentPage::getName(),
    CurrentPage::getBasePath()
);

if(defined('LANG')) {
    d(constant('LANG'));
}
else {
    d('no lang defined');
}
?>
