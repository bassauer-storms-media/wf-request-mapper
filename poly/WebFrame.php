<?php

/*
 * POLYFILLER CLASS - DOES NOT MATTER LATER
 */

class WebFrame {

    /*
     * TODO <<trim($uri2clean)>> IST NEU UND MUSS ÜBERNOMMEN WERDEN  (also das trim)
     */
    /*public static function cleanUri($uri2clean) {
        return implode('/', array_filter(preg_split('/[\\|\/]/', trim($uri2clean))));
    }*/

    /*public static function inst() {
        return (object)[
            'blade' => (object)[
                'filePath' => function($fq_clean_path, $base = null, $extension = null) {
                    $extension = $extension ?? \Config::BLADE_FILE_EXTENSION;
                    $base = $base ?? \Config::PAGES_DIR;
                    if (is_file($f = sprintf('%s/%s%s', $base, $fq_clean_path, $extension))) // for access to files that have no dir that they are placed in
                        $file = $f;
                    else
                        $file = sprintf('%s/%s/%s%s', $base, $fq_clean_path, basename($fq_clean_path), $extension);
                    return WebFrame::inst()->cleanUri($file);
                }
            ]
        ];
    }*/

}
