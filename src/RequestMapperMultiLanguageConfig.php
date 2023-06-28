<?php

class RequestMapperMultiLanguageConfig {

    private ?string $defaultLang = null;
    private int $maxLangAbbreviationLength = 2;
    private int $minLangAbbreviationLength = 2;
    private array $allowedLangs = [];

    /**
     * @return string|null
     */
    public function getDefaultLang() : ?string {
        return $this->defaultLang;
    }

    /**
     * @param string|null $defaultLang
     */
    public function setDefaultLang(?string $defaultLang) : void {
        $this->defaultLang = $defaultLang;
    }

    /**
     * @return int
     */
    public function getMaxLangAbbreviationLength() : int {
        return $this->maxLangAbbreviationLength;
    }

    /**
     * @param int $maxLangAbbreviationLength
     */
    public function setMaxLangAbbreviationLength(int $maxLangAbbreviationLength) : void {
        $this->maxLangAbbreviationLength = $maxLangAbbreviationLength;
    }

    /**
     * @return int
     */
    public function getMinLangAbbreviationLength() : int {
        return $this->minLangAbbreviationLength;
    }

    /**
     * @param int $minLangAbbreviationLength
     */
    public function setMinLangAbbreviationLength(int $minLangAbbreviationLength) : void {
        $this->minLangAbbreviationLength = $minLangAbbreviationLength;
    }

    /**
     * @return array
     */
    public function getAllowedLangs() : array {
        return $this->allowedLangs;
    }

    /**
     * @param array $allowedLangs
     */
    public function setAllowedLangs(array $allowedLangs) : void {
        $this->allowedLangs = $allowedLangs;
    }

}
