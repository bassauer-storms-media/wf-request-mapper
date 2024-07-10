 
404.php <

<hr>

<?php

use \serjoscha87\phpRequestMapper\{RequestMapper, CurrentPage};

d(
    RequestMapper::getCurrentPage(),
    RequestMapper::getCurrentPage()->getRequestMapper(),
    RequestMapper::getCurrentPage()->getFilePath()
);

d(
    'isDetailPageRequest?', CurrentPage::getRequestMapper()->isDetailPageRequest()
);

if(defined('LANG')) {
    d(constant('LANG'));
}

if(CurrentPage::getRequestMapper()->isDetailPageRequest()): ?>
    <b>
        404 because the detail page file does not exist.
    </b><?php
endif;
