<?php

interface IPage {

    //public function __construct(); // TODO define this as soon as I know what parameters are needed for the page instance

    public function __toString() : string;
    public function getName() : string|null;

    public function getUri() : string|null;
    public function getFilePath () : string|null;

    public function getRequestMapper () : RequestMapper|null;

}
