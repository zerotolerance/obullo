<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @license         public
 * @since           Version 1.0
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Obullo Security Helpers
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */

// --------------------------------------------------------------------

/**
* Set Cross Site Request Forgery Protection Cookie
*
* @return	string
*/
if( ! function_exists('_csrf_set_hash') ) 
{
    function _csrf_set_hash()
    {
        $_ob = load_class('Storage');

        if ($_ob->security->csrf_hash == '')
        {
            // If the cookie exists we will use it's value.  
            // We don't necessarily want to regenerate it with
            // each page load since a page could contain embedded 
            // sub-pages causing this feature to fail

            if (isset($_COOKIE[$_ob->security->csrf_cookie_name]) && 
                    $_COOKIE[$_ob->security->csrf_cookie_name] != '')
            {
                    return $_ob->security->csrf_hash = $_COOKIE[$_ob->security->csrf_cookie_name];
            }

            return $_ob->security->csrf_hash = md5(uniqid(rand(), TRUE));
        }

        return $_ob->security->csrf_hash;
    }
}

// --------------------------------------------------------------------

/**
 * Security Helper Constructor
 */
if( ! isset($_ob->security)) 
{
    $_ob = load_class('Storage');
    
    $_ob->security = new stdClass();
    $_ob->security->xss_hash            = '';
    $_ob->security->csrf_hash		= '';
    $_ob->security->csrf_expire		= 7200;  // Two hours (in seconds)
    $_ob->security->csrf_token_name	= 'ob_csrf_token';
    $_ob->security->csrf_cookie_name	= 'ob_csrf_token';

    /* never allowed, string replacement */
    $_ob->security->never_allowed_str   = array(
                                        'document.cookie'   => '[removed]',
                                        'document.write'    => '[removed]',
                                        '.parentNode'       => '[removed]',
                                        '.innerHTML'        => '[removed]',
                                        'window.location'   => '[removed]',
                                        '-moz-binding'      => '[removed]',
                                        '<!--'              => '&lt;!--',
                                        '-->'               => '--&gt;',
                                        '<![CDATA['         => '&lt;![CDATA['
                                        );
                                    
    /* never allowed, regex replacement */
    $_ob->security->never_allowed_regex = array(
                                        "javascript\s*:"            => '[removed]',
                                        "expression\s*(\(|&\#40;)"  => '[removed]', // CSS and IE
                                        "vbscript\s*:"              => '[removed]', // IE, surprise!
                                        "Redirect\s+302"            => '[removed]'
                                    );
    
    // CSRF config
    foreach(array('csrf_expire', 'csrf_token_name', 'csrf_cookie_name') as $key)
    {
        if (FALSE !== ($val = config_item($key)))
        {
            $_ob->security->{$key} = $val;
        }
    }

    // Append application specific cookie prefix
    if (config_item('cookie_prefix'))
    {
        $_ob->security->csrf_cookie_name = config_item('cookie_prefix') . $_ob->security->csrf_cookie_name;
    }

    // Set the CSRF hash
    _csrf_set_hash();
    
    log_me('debug', "Security Helper Initialized");
}


// --------------------------------------------------------------------

      
/**
 * Verify Cross Site Request Forgery Protection
 *
 * @return	object
 */
if( ! function_exists('csrf_verify') ) 
{
    function csrf_verify()
    {
        $_ob = load_class('Storage');

        // If no POST data exists we will set the CSRF cookie
        if (count($_POST) == 0)
        {
            return csrf_set_cookie();
        }

        // Do the tokens exist in both the _POST and _COOKIE arrays?
        if ( ! isset($_POST[$_ob->security->csrf_token_name]) OR 
                 ! isset($_COOKIE[$_ob->security->csrf_cookie_name]))
        {
            csrf_show_error();
        }

        // print_r($_POST);
        // Do the tokens match?
        if ($_POST[$_ob->security->csrf_token_name] != $_COOKIE[$_ob->security->csrf_cookie_name])
        {
            csrf_show_error();
        }

        // We kill this since we're done and we don't want to 
        // polute the _POST array
        unset($_POST[$_ob->security->csrf_token_name]);

        // Nothing should last forever
        unset($_COOKIE[$_ob->security->csrf_cookie_name]);

        _csrf_set_hash();

        csrf_set_cookie();

        log_me('debug', "CSRF token verified");
    }
}

// --------------------------------------------------------------------

/**
 * Set Cross Site Request Forgery Protection Cookie
 *
 * @return	object
 */
if( ! function_exists('csrf_set_cookie') ) 
{
    function csrf_set_cookie()
    {
        $_ob = load_class('Storage');

        $expire        = time() + $_ob->security->csrf_expire;
        $secure_cookie = (config_item('cookie_secure') === TRUE) ? 1 : 0;

        if ($secure_cookie)
        {
            # if your HTTP server NGINX add below the line to your fastcgi_params file.
            # fastcgi_param  HTTPS		  $ssl_protocol;
            # then $_SERVER['HTTPS'] variable will be available for PHP (fastcgi).

            $req = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : FALSE;

            if ( ! $req OR $req == 'off')
            {
                return FALSE;
            }
        }

        setcookie($_ob->security->csrf_cookie_name, $_ob->security->csrf_hash, $expire, config_item('cookie_path'), config_item('cookie_domain'), $secure_cookie);

        log_me('debug', "CRSF cookie set");
    }
}
// --------------------------------------------------------------------

/**
 * Show CSRF Error
 *
 * @return	void
 */
if( ! function_exists('csrf_show_error') ) 
{
    function csrf_show_error()
    {
        $msg = 'The action you have requested is not allowed.';

        if(i_ajax()) // Ajax request.
        {
            loader::helper('ob/form_send');

            echo form_send_error($msg);

            log_me('debug', "CSRF ajax attempt from ". i_ip_address());

            exit;
        }

        show_error($msg);

        log_me('debug', "CSRF attempt from ". i_ip_address());
    }
}

// --------------------------------------------------------------------

/**
 * Get CSRF Hash 
 *
 * Getter Method 
 *
 * @return 	string csrf_hash
 */
if( ! function_exists('get_csrf_hash') ) 
{
    function get_csrf_hash()
    {   
        return load_class('Storage')->security->csrf_hash;
    }
}

// --------------------------------------------------------------------

/**
 * Get CSRF Token Name
 *
 * Getter Method
 *
 * @return 	string 	csrf_token_name
 */
if( ! function_exists('get_csrf_token_name') ) 
{
    function get_csrf_token_name()
    {   
        return load_class('Storage')->security->csrf_token_name;
    }
}
// --------------------------------------------------------------------

/**
* XSS Clean
*
* Sanitizes data so that Cross Site Scripting Hacks can be
* prevented.  This function does a fair amount of work but
* it is extremely thorough, designed to prevent even the
* most obscure XSS attempts.  Nothing is ever 100% foolproof,
* of course, but I haven't been able to get anything passed
* the filter.
*
* Note: This function should only be used to deal with data
* upon submission.  It's not something that should
* be used for general runtime processing.
*
* This function was based in part on some code and ideas I
* got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
*
* To help develop this script I used this great list of
* vulnerabilities along with a few other hacks I've
* harvested from examining vulnerabilities in other programs:
* http://ha.ckers.org/xss.html
*
* @access   public
* @param    string
* @return   string
*/
if( ! function_exists('xss_clean') ) 
{
    function xss_clean($str, $is_image = FALSE)
    {
        $_ob = load_class('Storage');
        /*
        * Is the string an array?
        *
        */
        if (is_array($str))
        {
            while (list($key) = each($str))
            {
                $str[$key] = xss_clean($str[$key]);
            }

            return $str;
        }

        /*
        * Remove Invisible Characters
        */
        $str = remove_invisible_characters($str);

        // Validate Entities in URLs
	$str = _validate_entities($str);
        
         /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         *
         */
        $str = rawurldecode($str);
        
        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */
        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", '_convert_attribute', $str);
        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", '_html_entity_decode_callback', $str);

        /*
         * Remove Invisible Characters Again!
         */
        $str = remove_invisible_characters($str);

        /*
        * Convert all tabs to spaces
        *
        * This prevents strings like this: ja    vascript
        * NOTE: we deal with spaces between characters later.
        * NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
        * so we use str_replace.
        *
        */

        if (strpos($str, "\t") !== FALSE)
        {
            $str = str_replace("\t", ' ', $str);
        }

        /*
        * Capture converted string for later comparison
        */
        $converted_string = $str;

        /*
        * Not Allowed Under Any Conditions
        */

        foreach ($_ob->security->never_allowed_str as $key => $val)
        {
            $str = str_replace($key, $val, $str);   
        }

        foreach ($_ob->security->never_allowed_regex as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);   
        }

        /*
        * Makes PHP tags safe
        *
        *  Note: XML tags are inadvertently replaced too:
        *
        *    <?xml
        *
        * But it doesn't seem to pose a problem.
        *
        */
        if ($is_image === TRUE)
        {
            // Images have a tendency to have the PHP short opening and closing tags every so often
            // so we skip those and only do the long opening tags.
            $str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
        }
        else
        {
            $str = str_replace(array('<?', '?'.'>'),  array('&lt;?', '?&gt;'), $str);
        }

        /*
        * Compact any exploded words
        *
        * This corrects words like:  j a v a s c r i p t
        * These words are compacted back to their correct state.
        *
        */
        $words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
        
        foreach ($words as $word)
        {
            $temp = '';

            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++)
            {
                $temp .= substr($word, $i, 1)."\s*";
            }

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#('.substr($temp, 0, -3).')(\W)#is', '_compact_exploded_words', $str);
        }

        /*
        * Remove disallowed Javascript in links or img tags
        * We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared
        * to these simplified non-capturing preg_match(), especially if the pattern exists in the string
        */
        do
        {
            $original = $str;

            if (preg_match("/<a/i", $str))
            {
                $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", '_js_link_removal', $str);
            }

            if (preg_match("/<img/i", $str))
            {
                $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", '_js_img_removal', $str);
            }

            if (preg_match("/script/i", $str) OR preg_match("/xss/i", $str))
            {
                $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
            }
        }
        while($original != $str);

        unset($original);

        /*
        * Remove JavaScript Event Handlers
        *
        * Note: This code is a little blunt.  It removes
        * the event handler and anything up to the closing >,
        * but it's unlikely to be a problem.
        *
        */
        $event_handlers = array('[^a-z_\-]on\w*','xmlns');

        if ($is_image === TRUE)
        {
            /*
            * Adobe Photoshop puts XML metadata into JFIF images, including namespacing, 
            * so we have to allow this for images. -Paul
            */
            unset($event_handlers[array_search('xmlns', $event_handlers)]);
        }

        $str = preg_replace("#<([^><]+?)(".implode('|', $event_handlers).")(\s*=\s*[^><]*)([><]*)#i", "<\\1\\4", $str);

        /*
        * Sanitize naughty HTML elements
        *
        * If a tag containing any of the words in the list
        * below is found, the tag gets converted to entities.
        *
        * So this: <blink>
        * Becomes: &lt;blink&gt;
        *
        */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', '_sanitize_naughty_html', $str);

        /*
        * Sanitize naughty scripting elements
        *
        * Similar to above, only instead of looking for
        * tags it looks for PHP and JavaScript commands
        * that are disallowed.  Rather than removing the
        * code, it simply converts the parenthesis to entities
        * rendering the code un-executable.
        *
        * For example:    eval('some code')
        * Becomes:        eval&#40;'some code'&#41;
        *
        */
        $str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

        /*
        * Final clean up
        *
        * This adds a bit of extra precaution in case
        * something got through the above filters
        *
        */
        foreach ($_ob->security->never_allowed_str as $key => $val)
        {
            $str = str_replace($key, $val, $str);   
        }

        foreach ($_ob->security->never_allowed_regex as $key => $val)
        {
            $str = preg_replace("#".$key."#i", $val, $str);
        }

        /*
        *  Images are Handled in a Special Way
        *  - Essentially, we want to know that after all of the character conversion is done whether
        *  any unwanted, likely XSS, code was found.  If not, we return TRUE, as the image is clean.
        *  However, if the string post-conversion does not matched the string post-removal of XSS,
        *  then it fails, as there was unwanted XSS code found and removed/changed during processing.
        */

        if ($is_image === TRUE)
        {
            if ($str == $converted_string)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }

        log_me('debug', "XSS Filtering completed");
        return $str;
    }
}
// --------------------------------------------------------------------

/**
* Random Hash for protecting URLs
* 
* @version   0.1 
* @version   0.2 Obullo changes removed php_version
* @access    public
* @return    string
*/
if( ! function_exists('xss_hash') ) 
{
    function xss_hash()
    {
        $_ob = load_class('Storage');
        
        if ($_ob->security->xss_hash == '')
        {
            mt_srand();
            
            $_ob->security->xss_hash = md5(time() + mt_rand(0, 1999999999));
        }

        return $_ob->security->xss_hash;
    }
}

// --------------------------------------------------------------------

/**
* Compact Exploded Words
*
* Callback function for xss_clean() to remove whitespace from
* things like j a v a s c r i p t
*
* @access   public
* @param    type
* @return   type
*/
if( ! function_exists('_compact_exploded_words') ) 
{
    function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }
}
// --------------------------------------------------------------------

/**
* Sanitize Naughty HTML
*
* Callback function for xss_clean() to remove naughty HTML elements
*
* @access   private
* @param    array
* @return   string
*/
if( ! function_exists('_sanitize_naughty_html') ) 
{
    function _sanitize_naughty_html($matches)
    {
        // encode opening brace
        $str = '&lt;'.$matches[1].$matches[2].$matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);

        return $str;
    }
}
// --------------------------------------------------------------------

/**
* JS Link Removal
*
* Callback function for xss_clean() to sanitize links
* This limits the PCRE backtracks, making it more performance friendly
* and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
* PHP 5.2+ on link-heavy strings
*
* @access    private
* @param    array
* @return    string
*/
if( ! function_exists('_js_link_removal') ) 
{
    function _js_link_removal($match)
    {
        $attributes = _filter_attributes(str_replace(array('<', '>'), '', $match[1]));
        return str_replace($match[1], preg_replace("#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
    }
}

/**
* JS Image Removal
*
* Callback function for xss_clean() to sanitize image tags
* This limits the PCRE backtracks, making it more performance friendly
* and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
* PHP 5.2+ on image tag heavy strings
*
* @access   private
* @param    array
* @return   string
*/
if( ! function_exists('_js_img_removal') ) 
{
    function _js_img_removal($match)
    {
        $attributes = _filter_attributes(str_replace(array('<', '>'), '', $match[1]));
        return str_replace($match[1], preg_replace("#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
    }
}
// --------------------------------------------------------------------

/**
* Attribute Conversion
*
* Used as a callback for XSS Clean
*
* @access   public
* @param    array
* @return   string
*/
if( ! function_exists('_convert_attribute') ) 
{
    function _convert_attribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }
}
// --------------------------------------------------------------------

/**
* HTML Entity Decode Callback
*
* Used as a callback for XSS Clean
*
* @access   public
* @param    array
* @return   string
*/
if( ! function_exists('_html_entity_decode_callback') ) 
{
    function _html_entity_decode_callback($match)
    {
        $config  = core_class('Config');
        $charset = $config->item('charset');

        return _html_entity_decode($match[0], strtoupper($charset));
    }
}
// --------------------------------------------------------------------

/**
* HTML Entities Decode
*
* This function is a replacement for html_entity_decode()
*
* In some versions of PHP the native function does not work
* when UTF-8 is the specified character set, so this gives us
* a work-around.  More info here:
* http://bugs.php.net/bug.php?id=25670
*
* @access   private
* @param    string
* @param    string
* @return   string
*/
/* -------------------------------------------------
/*  Replacement for html_entity_decode()
/* -------------------------------------------------*/

/*
NOTE: html_entity_decode() has a bug in some PHP versions when UTF-8 is the
character set, and the PHP developers said they were not back porting the
fix to versions other than PHP 5.x.
*/
if( ! function_exists('_html_entity_decode') ) 
{
    function _html_entity_decode($str, $charset='UTF-8')
    {
        if (stristr($str, '&') === FALSE) return $str;

        // The reason we are not using html_entity_decode() by itself is because
        // while it is not technically correct to leave out the semicolon
        // at the end of an entity most browsers will still interpret the entity
        // correctly.  html_entity_decode() does not convert entities without
        // semicolons, so we are left with our own little solution here. Bummer.

        if (function_exists('html_entity_decode') && (strtolower($charset) != 'utf-8' OR version_compare(phpversion(), '5.0.0', '>=')))
        {
            $str = html_entity_decode($str, ENT_COMPAT, $charset);
            $str = preg_replace('~&#x(0*[0-9a-f]{2,5})~ei', 'chr(hexdec("\\1"))', $str);
            return preg_replace('~&#([0-9]{2,4})~e', 'chr(\\1)', $str);
        }

        // Numeric Entities
        $str = preg_replace('~&#x(0*[0-9a-f]{2,5});{0,1}~ei', 'chr(hexdec("\\1"))', $str);
        $str = preg_replace('~&#([0-9]{2,4});{0,1}~e', 'chr(\\1)', $str);

        // Literal Entities - Slightly slow so we do another check
        if (stristr($str, '&') === FALSE)
        {
            $str = strtr($str, array_flip(get_html_translation_table(HTML_ENTITIES)));
        }

        return $str;
    }
}
// --------------------------------------------------------------------

/**
* Filter Attributes
*
* Filters tag attributes for consistency and safety
*
* @access   public
* @param    string
* @return   string
*/
if( ! function_exists('_filter_attributes') ) 
{
    function _filter_attributes($str)
    {
        $out = '';

        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
        {
            foreach ($matches[0] as $match)
            {
                $out .= preg_replace("#/\*.*?\*/#s", '', $match);
            }
        }

        return $out;
    }
}
// --------------------------------------------------------------------

/**
* Hash encode a string
*
* @access	public
* @param	string
* @return	string
*/
if( ! function_exists('do_hash') ) 
{
    function do_hash($str, $type = 'sha1')
    {
        if ($type == 'sha1')
        {
            return sha1($str);
        }
        else
        {
            return md5($str);
        }
    }
}
// ------------------------------------------------------------------------

/**
* Strip Image Tags
*
* @access	public
* @param	string
* @return	string
*/
if( ! function_exists('strip_image_tags') ) 
{
    function strip_image_tags($str)
    {
        $str = preg_replace("#<img\s+.*?src\s*=\s*[\"'](.+?)[\"'].*?\>#", "\\1", $str);
        $str = preg_replace("#<img\s+.*?src\s*=\s*(.+?).*?\>#", "\\1", $str);

        return $str;
    }
}
// ------------------------------------------------------------------------

/**
* Convert PHP tags to entities
*
* @access	public
* @param	string
* @return	string
*/
if( ! function_exists('encode_php_tags') ) 
{
    function encode_php_tags($str)
    {
        return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
    }
}


/**
 * Validate URL entities
 *
 * Called by xss_clean()
 *
 * @param 	string	
 * @return 	string
 */
if( ! function_exists('_validate_entities') ) 
{
    function _validate_entities($str)
    {
        /*
         * Protect GET variables in URLs
         */

         // 901119URL5918AMP18930PROTECT8198

        $str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', xss_hash()."\\1=\\2", $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i',"\\1\\2;",$str);

        /*
         * Un-Protect GET variables in URLs
         */
        $str = str_replace(xss_hash(), '&', $str);

        return $str;
    }
}
/* End of file security.php */
/* Location: ./obullo/helpers/security.php */