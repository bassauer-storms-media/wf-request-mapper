<?php

class BasePathConfig {

    const STRIP_REQUEST_BASE = 0;

    private null|string|int $strip = null;
    private string $basePath = '';
    private ?IPage $fourOFourPage = null;

    public function __construct(string $basePath, null|string|int $strip = null, IPage $fourOFourPage = null) {
        //$this->basePath = ltrim($basePath, '/');

        if(realpath($basePath) === false) // if we can't identify the path as fully qualified, we must expect it is a relative path and therefor we need to strip guiding slashes
            $this->basePath = ltrim($basePath, '/');
        else
            $this->basePath = $basePath;

        $this->fourOFourPage = $fourOFourPage ?? new Default404Page();

        $this->strip = $strip;
    }

    public function __toString() : string {
        return $this->basePath;
    }

    /**
     * @return null|string|int
     */
    public function getStrip() : null|string|int {
        return $this->strip;
    }

    /**
     * allow to set a string to be stripped from the basepath or a the 'STRIP_REQUEST_BASE' constant of this class in order to make [?the url being stirpped by that string?]
     * @param null|string|int $strip
     */
    public function setStrip(null|string|int $strip) : self {
        $this->strip = $strip;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath() : string {
        return $this->basePath;
    }

    /**
     * @param string $base
     */
    public function setBasePath(string $basePath) : self {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return IPage|null
     */
    public function getFourOFourPage() : ?IPage {
        return $this->fourOFourPage;
    }

    /**
     * @param IPage|null $fourOFourPage
     */
    public function setFourOFourPage(?IPage $fourOFourPage) : void {
        $this->fourOFourPage = $fourOFourPage;
    }

}
