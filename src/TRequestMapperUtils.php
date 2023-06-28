<?php

trait TRequestMapperUtils {

    public static function isReal404() {
        $is_user_triggered_404 = strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false; // none user triggered: all requests that do not expect back a mime type of text/html
        return $is_user_triggered_404 === false;
    }

    public function reroute($path, $status_code = 200) {
        $path = sprintf('/%s', ltrim($path,'/'));
        header(sprintf('HTTP/1.1 %s %s', $status_code, $this->statusCodes[$status_code]));
        header("Location: $path");
        exit;
    }

    /**
     * Returns a clean formatted uri that always has the same pattern
     * Examples:
     *    /home///test/      =>     /home/test
     *    home/test          =>     /home/test
     *    /home/test         =>     /home/test
     *    /home/test/        =>     /home/test
     * @param string $uri2clean
     * @return string the clean and expective path
     */
    static function cleanUri(string $uri2clean) : string {
        return '/' .  implode('/', array_filter(explode('/', $uri2clean)));
    }

    private function filePath($uri = null, $base = null) {
        $uri = $uri ?? $this->uri;
        $base = $base ?? $this->getConfig()->getDefaultBasePath();
        $extension = $this->getConfig()->getPageFileExtension();
        if (is_file($f = sprintf('%s%s%s', $base, $uri, $extension))) {
            /*
              * example: for resolving requests like this
              * /foobar/test
              * to fs structure like this:
              * /pages/foobar/test.blade.php
              */
            $file = $f;
        }
        elseif($this->isDetailPageRequest()) {
            $page_and_query = [];
            preg_match('/^(?<page_file>.*\/detail)\/(?<query>.*)$/', $this->uri, $page_and_query);
            //$page_base = ltrim($this->getFileBaseDir(), '/');
            //$page_base = $this->getFileBaseDir();
            $page_base = $this->getBasePathConfig()->getBasePath();
            //$this->query = $page_and_query['query'];
            if($this->getConfig()->doesDynamicDetailPageQueryOverrideGet())
                $_GET['query'] = $page_and_query['query'];
            return $this->filePath($page_and_query['page_file'], $page_base);
            //return $page_and_query['page_file'];
            //dd($page_and_query['page_file'], $page_base);
        }
        elseif($f = sprintf('%s/%s/%s%s', $base, $uri, basename($uri), $extension)) {
            /*
             * example: for resolving requests like this:
             * /foobar/test
             * to fs structure like this:
             * /pages/foobar/test/test.blade.php
             */
            $file = $f;
        }
        else
            $file = null;
        //return RequestMapper::cleanUri($file);
        return $file;
    }

}
