<?php
namespace Sil\SilAuth\text;

class Text
{
//    public static function contains(string $haystack, string $needle)
//    {
//        return mb_strpos($haystack, $needle) !== false;
//    }
    
    /**
     * See if the given string (haystack) starts with the given prefix (needle).
     * 
     * @param string $haystack The string to search.
     * @param string $needle The string to search for.
     * @return boolean
     */
    public static function startsWith(string $haystack, string $needle)
    {
        $length = mb_strlen($needle);
        return (mb_substr($haystack, 0, $length) === $needle);
    }
}
