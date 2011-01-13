<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2009.
 * @filesource
 * @license
 */
 
Class LangException extends CommonException {}

/**
 * Obullo Language Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Language
 * @author      Ersin Guvenc
 * @link        
 */
 
if( ! isset($_ob->lang)) 
{
    $_ob = base_register('Empty');
    $_ob->lang = new stdClass();

    $_ob->lang->language  = array();
    $_ob->lang->is_loaded = array();

    log_me('debug', "Language Helper Initialized");
}

// --------------------------------------------------------------------

/**
* Load a language file
*
* @access   public
* @param    mixed    the name of the language file to be loaded. Can be an array
* @param    string   the language (english, etc.)
* @return   mixed
*/
if( ! function_exists('lang_load') ) 
{
    function lang_load($langfile = '', $idiom = '', $dir = 'base', $return = FALSE)
    {     
        $_ob = base_register('Empty');
        
        if (in_array($langfile, $_ob->lang->is_loaded, TRUE))
        return;  
        
        if ($idiom == '')
        {
            $deft_lang = this()->config->item('language');
            $idiom = ($deft_lang == '') ? 'english' : $deft_lang;
        }
        
        switch ($dir)
        {
            case 'local':
             $folder = DIR .$GLOBALS['d']. DS .'lang'. DS;                            
             break;
            
            case 'global':
             $folder = APP .'lang'. DS .$idiom. DS;
             break;
             
            case 'base':
             $folder = BASE.'lang'. DS .$idiom. DS;  
             break;
        }

        if( ! is_dir($folder))
        return;
        
        $lang = get_static($langfile, 'lang', $folder);     // Obullo changes ...
        
        if ( ! isset($lang))
        {
            log_me('error', 'Language file contains no data: lang' . DS .$idiom. DS . $langfile. EXT);
            return;
        }

        if ($return)
        return $lang;

        $_ob->lang->is_loaded[] = $langfile;
        $_ob->lang->language    = array_merge($_ob->lang->language, $lang);
        
        profiler_set('lang_files', $langfile, $langfile);
        unset($lang);

        log_me('debug', 'Language file loaded: lang' . DS .$idiom. DS .$langfile. EXT);
        return TRUE;
    }
}

/**
* Fetch a item of text from the language array
*
* @access   public
* @param    string  $item the language item
* @return   string
*/
if( ! function_exists('lang') ) 
{
    function lang($item = '')
    {
        $_ob = base_register('Empty');
        
        $item = ($item == '' OR ! isset($_ob->lang->language[$item])) ? FALSE : $_ob->lang->language[$item];
        return $item;
    }
}

/* End of file lang.php */
/* Location: ./obullo/helpers/loaded/lang.php */
