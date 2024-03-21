<?php

namespace phpRequestMapper;

interface IPage {

    public function __toString() : string;
    public function getName() : string|null;

    public function getUri() : string|null;
    public function getFilePath () : string|null;
    public function getBasePath () : string|null;
    public function is404 () : bool;
    public function isDetailPage () : bool;

    public function getRequestMapper () : RequestMapper|null;

}
