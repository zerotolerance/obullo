<?php defined('BASE') or die('Access Denied !');

/**
 * UTF8::ucwords
 *
 */
function utf8_ucwords($str)
{
    if (UTF8::is_ascii($str))
        return ucwords($str);

    if(strpos($str, 'i') === 0)  // i - I problem in Turkish Characters .
    {
        loader::helper('ob/utf8/substr');
        
        $str = 'İ'. utf8_substr($str, 1);
    }
        
    // [\x0c\x09\x0b\x0a\x0d\x20] matches form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns.
    // This corresponds to the definition of a 'word' defined at http://php.net/ucwords
    return preg_replace(
        '/(?<=^|[\x0c\x09\x0b\x0a\x0d\x20])[^\x0c\x09\x0b\x0a\x0d\x20]/ue',
        'UTF8::strtoupper(\'$0\')',
        $str
    );
}