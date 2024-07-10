<?php

declare(strict_types=1);

namespace serjoscha87\phpRequestMapper;

/**
 * Simple wrapper class around uri string that generates a type and ensures that a given uri is always clean (solid), unified and without a query attached.
 * This makes juggling with uris in the main code a lot easier because we can always rely on the uri to be in an ultimate clean & unified state.
 */
class SolidUri {

    protected string $cleanUri;
    protected string $originalUri;
    protected string $originalUriQueryless;

    protected string $query;

    protected string $_prefix;

    public function __construct(string $uri, string $prefix = '/') {
        $this->_prefix = $prefix;

        $plain_uri = strtok($uri, '?'); // < uri without query
        $this->query = substr($uri, strlen($plain_uri)); // < will be an empty string if no query is present

        $this->cleanUri = self::cleanUri($plain_uri, $prefix);

        $this->originalUri = $uri;
        $this->originalUriQueryless = $plain_uri;
    }

    public function __toString() : string {
        return $this->cleanUri;
    }

    /**
     * @return string the uri without any query
     */
    public function getUri() : string {
        return $this->cleanUri;
    }

    public function getOriginalUri(bool $withQuery = true) : string {
        return $withQuery ? $this->originalUri : $this->originalUriQueryless;
    }

    public function getPrefix() : string {
        return $this->_prefix;
    }

    /**
     * Cleans the passed uri and returns whether it is equal to the current (clean) uri.
     * @throws \Exception
     */
    public function equals(string|SolidUri $other, bool $checkPrefix = true) : bool {
        if($checkPrefix && $other->getPrefix() !== $this->_prefix)
            throw new \Exception('Uri prefixes do not match. Either make sure to compare uris with the same prefix or set $checkPrefix to false.');

        return $this->cleanUri === ($other instanceof SolidUri ? $other->getUri() : self::cleanUri($other, $this->_prefix));
    }

    /**
     * alias for equals
     * @throws \Exception
     */
    public function eq(string|SolidUri $other) : bool {
        return $this->equals($other);
    }


    public static string $prefix = '/';

    /**
     * Returns a clean formatted uri that always has the same pattern
     * Examples:
     *    /home///test/      =>     /home/test
     *    home/test          =>     /home/test
     *    /home/test         =>     /home/test
     *    /home/test/        =>     /home/test
     *    /                  =>     /
     *    <none>             =>     /
     * @param string $uri the uri to clean
     * @return string the clean and expective path
     */
    public static function cleanUri(string $uri, ?string $prefix = null) : string {
        return ($prefix ?? self::$prefix) . implode('/', array_filter(explode('/', $uri)));
    }

    public function hasQuery () : bool {
        return trim($this->query) !== '';
    }

    public function getQuery () : string {
        return $this->query;
    }

}
