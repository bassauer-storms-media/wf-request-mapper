<h1>pages/foobar/sub/another.php</h1>

<?php

if(method_exists($this, 'getSliderImages')) {
    d(\serjoscha87\phpRequestMapper\CurrentPage::getBasePath(), $this->getBasePath(), $this->getSliderImages());
}
