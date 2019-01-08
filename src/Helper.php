<?php

namespace PiedWeb\UrlHarvester;

use ForceUTF8\Encoding;

class Helper
{
    public function clean(string $source)
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

    public static function removeAccent($str)
    {
        if ($str !== mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32')) {
            $str = mb_convert_encoding($str, 'UTF-8');
        }
        $str = htmlentities($str, ENT_NOQUOTES, 'UTF-8');
        $str = preg_replace('`&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i', '$1', $str);

        return $str;
    }

    public static function imageToTxt($txt)
    {
        $html = new simple_html_dom();
        $html->load($txt);
        foreach ($html->find('img') as $img) {
            $alt = isset($img->alt) ? $img->alt : '-';
            $alt = substr($alt, 0, 300).(strlen($alt) > 300 ? '~' : '');
            $txt = str_replace($img->outertext, '!['.$alt.']('.$src.')', $txt);
        }

        return $txt;
    }
}
