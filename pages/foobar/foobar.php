
<h1>pages/foobar/foobar.php</h1>

<?php

if(method_exists($this, 'getSliderImages')) {
    d($this->getSliderImages());
}

if(defined('LANG')) {
    d(constant('LANG'));
}
?>
