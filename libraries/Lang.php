<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2012.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo
 * @author          obullo.com
 * @copyright       Obullo Team
 * @filesource
 * @license
 */

// --------------------------------------------------------------------

/**
 * Obullo Language Class
 *
 * @package     Obullo
 * @subpackage  Libraries
 * @category    Language
 * @author      Obullo Team
 * @link        
 */

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
        $lang = lib('ob/Lang');
        
        $item = ($item == '' OR ! isset($lang->language[$item])) ? FALSE : $lang->language[$item];
        
        return $item;
    }
}

// --------------------------------------------------------------------

Class OB_Lang {
    
    public $language  = array();
    public $is_loaded = array();

    function __construct()
    {
        log_me('debug', "Language Helper Initialized");    
    }

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
    public function load($langfile = '', $idiom = '', $dir = '', $return = FALSE)
    {
        if ($idiom == '')
        {
            $deft_lang = lib('ob/Config')->item('language');
            $idiom = ($deft_lang == '') ? 'english' : $deft_lang;
        }

        if(strpos($langfile, 'ob/') === 0)  // Obullo Core Lang
        {
            $langfile = strtolower(substr($langfile, 3));
            
            $data = array('filename' => $langfile, 'path' => BASE .'lang'. DS. trim($idiom, '/'));
            
            if(file_exists(APP .'lang'. DS. trim($idiom, '/') . DS. $langfile. EXT)) // check app path
            {
                $data = array('filename' => $langfile, 'path' => APP .'lang'. DS. trim($idiom, '/'));
            }
        } 
        else 
        {
            $data = loader::load_file($filename, 'lang', FALSE, $idiom);
        }
        
        if (in_array($langfile, $this->is_loaded, TRUE))
        {
            return;
        }
        
        if( ! is_dir($data['path']))
        {
            throw new Exception('The language folder '.$data['path'].' seems not a folder.');
        }

        $lang = get_static($data['filename'], 'lang', rtrim($data['path'], DS)); 
        

        if ( ! isset($lang))
        {
            log_me('error', 'Language file contains no lang variable: ' . $data['path'] . DS . $data['filename']. EXT);
            
            return;
        }

        if ($return)
        {
            return $lang;
        }

        $this->is_loaded[] = $langfile;
        $this->language    = array_merge($this->language, $lang);

        unset($lang);

        log_me('debug', 'Language file loaded: '.$data['path'] . DS . $data['filename']. EXT);
        
        return TRUE;
    }
}

/* End of file lang.php */
/* Location: ./obullo/helpers/core/lang.php */