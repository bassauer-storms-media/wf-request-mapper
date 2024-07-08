 
404.php (of pages2) <

<hr>

<?php
/*
d(
    \serjoscha87\phpRequestMapper\CurrentRequest::getPage(),
    \serjoscha87\phpRequestMapper\CurrentRequest::getPage()->getRequestMapper(),
    \serjoscha87\phpRequestMapper\CurrentRequest::getPage()->getFilePath()
);
*/

d(
    \serjoscha87\phpRequestMapper\RequestMapper::getCurrentPage(),
    \serjoscha87\phpRequestMapper\RequestMapper::getCurrentPage()->getRequestMapper(),
    \serjoscha87\phpRequestMapper\RequestMapper::getCurrentPage()->getFilePath()
);

if(defined('LANG')) {
    d(constant('LANG'));
}

