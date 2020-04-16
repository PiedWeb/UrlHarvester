<?php

namespace PiedWeb\UrlHarvester;

use ForceUTF8\Encoding;

class Helper
{
    public static function clean(string $source)
    {
        return trim(preg_replace('/\s{2,}/', ' ', Encoding::toUTF8($source)));
    }

    public static function htmlToPlainText($str, $keepN = false)
    {
        $str = preg_replace('#<(style|script).*</(style|script)>#siU', ' ', $str);
        $str = preg_replace('#</?(br|p|div)>#siU', "\n", $str);
        $str = preg_replace('/<\/[a-z]+>/siU', ' ', $str);
        $str = str_replace(["\r", "\t"], ' ', $str);
        $str = strip_tags(preg_replace('/<[^<]+?>/', ' ', $str));
        if ($keepN) {
            $str = preg_replace('/ {2,}/', ' ', $str);
        } else {
            $str = preg_replace('/\s+/', ' ', $str);
        }

        return $str;
    }
}
