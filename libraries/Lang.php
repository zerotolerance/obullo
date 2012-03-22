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

Class LangException extends CommonException {}

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
        if(strpos($langfile, 'ob/') === 0)
        {
            $langfile = substr($langfile, 3);
            $dir      = 'base';
        }
        
        if(strpos($langfile, 'app/') === 0)
        {
            $langfile = substr($langfile, 4);
        }
        
        if (in_array($langfile, $this->is_loaded, TRUE))
        {
            return;
        }
        
        if ($idiom == '')
        {
            $deft_lang = lib('ob/Config')->item('language');
            $idiom = ($deft_lang == '') ? 'english' : $deft_lang;
        }
        
        $file_info = $this->_load_file($langfile, $dir, $idiom);
        $folder    = $file_info['path'];
        
        if(is_array($folder))   // Merge Applicaiton and Module language files ..
        { 
           $lang = array(); 
           foreach($folder as $folder_val)
           {
               if(is_dir($folder_val))
               {
                   $lang = array_merge($lang, get_static($file_info['filename'], 'lang', rtrim($folder_val, DS)));
               }
           }
        } 
        else 
        {
            if( ! is_dir($folder))
            {
                throw new LangException('The language folder '.$folder.' seems not a folder.');
            }
            
            $lang = get_static($file_info['filename'], 'lang', rtrim($folder, DS)); 
        }

        if ( ! isset($lang))
        {
            log_me('error', 'Language file contains no lang variable: ' . $file_info['path'] . DS . $file_info['filename']. EXT);
            
            return;
        }

        if ($return)
        {
            return $lang;
        }

        $this->is_loaded[] = $langfile;
        $this->language    = array_merge($this->language, $lang);
        
        profiler_set('lang_files', $file_info['path'] . $file_info['filename'], $file_info['path'] . DS .$file_info['filename']. EXT);
        
        unset($lang);

        log_me('debug', 'Language file loaded: '.$file_info['path'] . DS . $file_info['filename']. EXT);
        
        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
    * Language loader
    * 
    * @access private
    * @param  string $file_url
    * @param  string $dir
    * @param  string $extra_path
    * @return mixed
    */
    public function _load_file($file_url, $dir = '', $extra_path = '')
    {
        $sub_module_path = $GLOBALS['sub_path'];  // sub.module path
        
        if($extra_path != '')
        {
            $extra_path = str_replace('/', DS, trim($extra_path, '/')). DS;
        }
        
        $file_url = strtolower($file_url);
                
        //-------------- OBULLO LANG --------------//
        
        if($dir == 'base')  // if base lang
        { 
            $filename = $file_url;
            
            if(file_exists(APP .'lang'. DS. trim($extra_path, '/') . DS. $filename. EXT)) // check app path
            {
                return array('filename' => $filename, 'path' => APP .'lang'. DS. trim($extra_path, '/'));
            }
            
            return array('filename' => $file_url, 'path' => BASE .'lang'. DS. trim($extra_path, '/'));
        }
        
        //-------------- OBULLO LANG --------------//
        
        if(strpos($file_url, 'sub.') === 0)   // sub.module/module folder request
        {
            $paths          = explode('/', $file_url); 
            $filename       = array_pop($paths);       // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // /filename/sub/file.php  sub dir support
            }
            
            $module_path = MODULES .$sub_modulename. DS .'lang'. DS .$sub_path. $extra_path;
            
            if( ! file_exists($module_path. $filename. EXT))
            {
                throw new LangException('Unable locate the file '. $module_path. $filename. EXT);
            }

        }
        elseif(strpos($file_url, '../sub.') === 0)   // sub.module/module folder request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths          = explode('/', substr($file_url, 3)); 
            $filename       = array_pop($paths);       // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            $modulename     = array_shift($paths);     // get module name
            
            $sub_module_path = $sub_modulename. DS .SUB_MODULES;
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }
            
            if($modulename == '')
            {
                $module_path = MODULES .$sub_modulename. DS .'lang'. DS .$extra_path;
            }
            else
            {
                $module_path = MODULES .$sub_modulename. DS .SUB_MODULES .$modulename. DS .'lang'. DS . $sub_path. $extra_path;
            }
            
            if( ! file_exists($module_path. $filename. EXT))  // first check module path
            {
                throw new LangException('Unable locate the file '. $module_path. $filename. EXT);
            }
            
        }
        elseif(strpos($file_url, '../') === 0)  // if  ../modulename/file request
        {
            $sub_module_path = '';  // clear sub module path
            
            $paths      = explode('/', substr($file_url, 3));
            $filename   = array_pop($paths);          // get file name
            $modulename = array_shift($paths);        // get module name
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }
            
            //---------- Extension Support -----------//
            
            if(extension('enabled', $modulename) == 'yes') // If its a enabled extension
            {
                if(strpos(extension('path', $modulename), 'sub.') === 0) // If extension working path is a sub.module.
                {
                    $file_url = '../'.extension('path', $modulename).'/'.$modulename.'/'.$filename;

                    if($sub_path != '')
                    {
                        $file_url = '../'.extension('path', $modulename).'/'.$modulename.'/'.str_replace(DS, '/', $sub_path).'/'.$filename;
                    }
     
                    return $this->_load_file($file_url, $dir, $extra_path);
                }
            }
            
            //---------- Extension Support -----------//
            
            $module_path = MODULES .$modulename. DS .'lang'. DS .$sub_path. $extra_path;
            
            if( ! file_exists($module_path. $filename. EXT))  // first check module path
            {
                throw new LangException('Unable locate the file '. $module_path. $filename. EXT);
            }
            
        }
        else    // if current modulename/file
        {
            $filename = $file_url;          
            $paths    = array();
            if( strpos($filename, '/') !== FALSE)
            {
                $paths    = explode('/', $filename);
                $filename = array_pop($paths);
            }

            $modulename = lib('ob/Router')->fetch_directory();
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }
            
            $sub_module = lib('ob/URI')->fetch_sub_module();
            
            if($sub_module != '' AND file_exists(MODULES .'sub.'.$sub_module. DS .'lang'. DS .$sub_path. $extra_path. $filename. EXT))
            {
                $module_path = MODULES .'sub.'.$sub_module. DS .'lang'. DS .$sub_path. $extra_path;
            } 
            else
            {
                $module_path = MODULES .$sub_module_path.$modulename. DS .'lang'. DS .$sub_path. $extra_path;
            }
            
            if( ! file_exists($module_path. $filename. EXT))  // first check module path
            {
                throw new LangException('Unable locate the file '. $module_path. $filename. EXT);
            }
        }
        
        $path = APP .'lang'. DS .$sub_path .$extra_path;
        
        if(file_exists($module_path. $filename. EXT))  // first check module path
        {
            if(file_exists($path. $filename. EXT))    // we send array so we will merge module and application $lang
            {                                         // variables.
                $path = array($path, $module_path);
            }
            else
            {
                $path = $module_path;
            }
        }

        return array('filename' => $filename, 'path' => $path);
    }
    
}

/* End of file lang.php */
/* Location: ./obullo/helpers/core/lang.php */