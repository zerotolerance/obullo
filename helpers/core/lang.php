<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
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
    $_ob = load_class('Storage');
    $_ob->lang = new stdClass();    // Create new language Object.

    $_ob->lang->language  = array();
    $_ob->lang->is_loaded = array();

    log_me('debug', "Language Helper Initialized");
}

// --------------------------------------------------------------------

/**
* Load a language file
*
* @access   public
* @param    string   $langfile the name of the language file to be loaded. Can be an array
* @param    string   $idiom the language folder (english, etc.)
* @param    string   $dir is it base language file ?
* @param    bool     $return return to $lang variable if you don't merge
* @return   mixed
*/
if( ! function_exists('lang_load') ) 
{
    function lang_load($langfile = '', $idiom = '', $dir = '', $return = FALSE)
    {     
        $_ob = load_class('Storage');
        
        if (in_array($langfile, $_ob->lang->is_loaded, TRUE))
        return;  
        
        if ($idiom == '')
        {
            $deft_lang = this()->config->item('language');
            $idiom = ($deft_lang == '') ? 'english' : $deft_lang;
        }
        
        $file_info = _lang_load_file($langfile, $dir, $idiom);
        $folder    = $file_info['path'];
        
        if( ! is_dir($folder))
        return;
        
        $lang = get_static($file_info['filename'], 'lang', $folder);     // Obullo changes ...
        
        if ( ! isset($lang))
        {
            log_me('error', 'Language file contains no lang variable: ' . $file_info['path'] . DS . $file_info['filename']. EXT);
            return;
        }

        if ($return)
        return $lang;

        $_ob->lang->is_loaded[] = $langfile;
        $_ob->lang->language    = array_merge($_ob->lang->language, $lang);
        
        profiler_set('lang_files', $file_info['path'] . $file_info['filename'], $file_info['path'] . DS .$file_info['filename']. EXT);
        
        unset($lang);

        log_me('debug', 'Language file loaded: '.$file_info['path'] . DS . $file_info['filename']. EXT);
        return TRUE;
    }
}

// --------------------------------------------------------------------

/**
* Language loader
* 
* @access private
* @param  string $file_url
* @param  string $base
* @param  string $extra_path
* @return mixed
*/
if( ! function_exists('_lang_load_file'))
{
    function _lang_load_file($file_url, $base = '', $extra_path = '')
    {
        if($extra_path != '')
        {
            $extra_path = str_replace('/', DS, trim($extra_path, '/')). DS;
        }
        
        $file_url = strtolower($file_url);
        
        if(strpos($file_url, '../') === 0)  // if  ../modulename/file request
        {
            $paths      = explode('/', substr($file_url, 3));
            $filename   = array_pop($paths);          // get file name
            $modulename = array_shift($paths);        // get module name
        }
        else    // if current modulename/file
        {
            $filename = $file_url;          
            $paths    = array();
            if( strpos($filename, '/') !== FALSE)
            {
                $paths      = explode('/', $filename);
                $filename   = array_pop($paths);
            }

            $modulename = (isset($GLOBALS['d'])) ? $GLOBALS['d'] : core_class('Router')->fetch_directory();
        }
        
        //-------------- BASE LANG --------------//
        
        if($base == 'base')  // if base lang
        { 
            if(file_exists(APP .'lang'. DS. trim($extra_path, '/') . DS. $filename. EXT)) // check app path
            {
                return array('filename' => $filename, 'path' => APP .'lang'. DS. trim($extra_path, '/'));
            }
            
            return array('filename' => $file_url, 'path' => BASE .'lang'. DS. trim($extra_path, '/'));
        }
        
        //-------------- BASE LANG --------------//

        $sub_path   = '';
        if( count($paths) > 0)
        {
            $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
        }
        
        $path        = APP .'lang'. DS .$sub_path .$extra_path;
        $module_path = MODULES .$modulename. DS .'lang'. DS .$sub_path. $extra_path;
        
        if(file_exists($module_path. $filename. EXT))  // first check module path
        {
            $path = $module_path;
        }
        
        return array('filename' => $filename, 'path' => $path);
    }
}


// --------------------------------------------------------------------

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
        $_ob = load_class('Storage');
        
        $item = ($item == '' OR ! isset($_ob->lang->language[$item])) ? FALSE : $_ob->lang->language[$item];
        
        return $item;
    }
}




/* End of file lang.php */
/* Location: ./obullo/helpers/core/lang.php */