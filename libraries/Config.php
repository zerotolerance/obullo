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
 
Class ConfigException extends CommonException {}  

/**
 * Obullo Config Class

 * This class contains functions that enable config files to be managed
 *
 * @package     Obullo
 * @subpackage  Libraries
 * @category    Libraries
 * @author      Obullo Team
 * @link        
 */
Class OB_Config
{    
    public $config          = array();
    public $is_loaded       = array();

    /**
    * Constructor
    *
    * Sets the $config data from the primary config.php file as a class variable
    *
    * @access  public
    * @return  void
    */
    public function __construct()
    {
        // Warning : Do not use lib($class);
        // 
        // Don't load any library in ***** __construct ******* function because of Obullo use 
        // the Config class __construct() method at Bootstrap loading level. When you try loading any library
        // in here you will get a Fatal Error.
        
        $this->config = get_config();
    }
      
    // --------------------------------------------------------------------
    
    /**
    * Load Config File
    *
    * @access   public
    * @param    string    the config file name
    * @return   boolean   if the file was loaded correctly
    */    
    public function load($file_url = '', $use_sections = FALSE, $fail_gracefully = FALSE)
    { 
        $file_info = $this->_load_file($file_url);
        
        $file      = ($file_info['filename'] == '') ? 'config' : str_replace(EXT, '', $file_info['filename']);
        $file_path = $file_info['path']; // Unique config files.
    
        if (in_array($file_info['path'] .$file. EXT, $this->is_loaded, TRUE))
        {
            return TRUE;
        }
        
        if ( ! file_exists($file_info['path'] .$file. EXT))
        {
            if ($fail_gracefully === TRUE)
            {
                return FALSE;
            }
            
            throw new ConfigException('The configuration file '.$file_info['path'] .$file. EXT .' does not exist.');
        }
    
        ######################
        
        include($file_info['path'] .$file. EXT);
                
        ######################

        if ( ! isset($config) OR ! is_array($config))
        {
            if ($fail_gracefully === TRUE)
            {
                return FALSE;
            }
            
            throw new ConfigException('Your '.$file. EXT.' file does not appear to contain a valid configuration array. Please create 
            $config variables in your ' . $file. EXT);
        }

        if ($use_sections === TRUE)
        {
            if (isset($this->config[$file]))
            {
                $this->config[$file] = array_merge($this->config[$file], $config);
            }
            else
            {
                $this->config[$file] = $config;
            }
        }
        else
        {
            $this->config = array_merge($this->config, $config);
        }

        $this->is_loaded[] = $file_info['path'] .$file. EXT;
        
        profiler_set('config_files', $file_path .$file. EXT, $file_path .$file. EXT);

        unset($config);

        log_me('debug', 'Config file loaded: '.$file_info['path'] .$file. EXT);
        
        return TRUE;
    }
      
    // --------------------------------------------------------------------

    /**
    * Load config file.
    * 
    * @param  string $file_url
    * @param  string $extra_path
    * @return array
    */
    public function _load_file($file_url, $extra_path = '')
    {   
        $sub_module_path = $GLOBALS['sub_path'];
        
        if($extra_path != '')
        {
            $extra_path = str_replace('/', DS, trim($extra_path, '/')) . DS;
        }
        
        $file_url  = strtolower(trim($file_url, '/'));
        
        if(strpos($file_url, '../sub.') === 0)   // sub.module/module folder request
        {
            $paths          = explode('/', substr($file_url, 3)); 
            $filename       = array_pop($paths);       // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            $modulename     = array_shift($paths);     // get module name
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;
            }
            
            if($modulename == '')
            {
                $module_path = MODULES .$sub_modulename. DS .'config'. DS .$extra_path;
            }
            else
            {
                $module_path = MODULES .$sub_modulename. DS .SUB_MODULES .$modulename. DS .'config'. DS . $sub_path. $extra_path;
            }
            
            if( ! file_exists($module_path. $filename. EXT))  // first check module path
            {
                throw new ConfigException('Unable locate the file '. $module_path. $filename. EXT);
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
                $sub_path = implode(DS, $paths) . DS;  // .modulename/folder/sub/file.php  sub dir support
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
                    
                    return $this->_load_file($file_url, $extra_path);
                }
            }
            
            //---------- Extension Support -----------//
            
            $module_path = MODULES .$sub_module_path.$modulename. DS .'config'. DS .$sub_path. $extra_path;
            
            if( ! file_exists($module_path. $filename. EXT))  // first check module path
            {
                throw new ConfigException('Unable locate the file '. $module_path. $filename. EXT);
            }
            
        }
        else       // if current modulename/file
        {
            $filename = $file_url;          
            $paths    = array();
            if( strpos($filename, '/') !== FALSE)
            {
                $paths      = explode('/', $filename);
                $filename   = array_pop($paths);
            }

            $modulename = lib('ob/Router')->fetch_directory();
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }
            
            $sub_module = lib('ob/URI')->fetch_sub_module();
            
            if($sub_module != '' AND file_exists(MODULES .'sub.'.$sub_module. DS .'config'. DS .$sub_path. $extra_path. $filename. EXT))
            {
                $module_path = MODULES .'sub.'.$sub_module. DS .'config'. DS .$sub_path. $extra_path;
            } 
            else
            {
                $module_path = MODULES .$sub_module_path.$modulename. DS .'config'. DS .$sub_path. $extra_path;
            }
            
            if( ! file_exists($module_path. $filename. EXT))  // first check module path
            {
                throw new ConfigException('Unable locate the file '. $module_path. $filename. EXT);
            }
        }
        
        $path = APP .'config'. DS .$sub_path .$extra_path;        
        
        if(file_exists($module_path. $filename. EXT))  // first check module path
        {
            $path = $module_path;
        }

        return array('filename' => $filename, 'path' => $path);
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Fetch a config file item
    *
    *
    * @access   public
    * @param    string    the config item name
    * @param    string    the index name
    * @param    bool
    * @return   string
    */
    public function item($item, $index = '')
    {    
        if ($index == '')
        {    
            if ( ! isset($this->config[$item]))
            {
                return FALSE;
            }

            $pref = $this->config[$item];
        }
        else
        {
            if ( ! isset($this->config[$index]))
            {
                return FALSE;
            }

            if ( ! isset($this->config[$index][$item]))
            {
                return FALSE;
            }

            $pref = $this->config[$index][$item];
        }

        return $pref;
    }
    
    // --------------------------------------------------------------------

    /**
    * Fetch a config file item - adds slash after item
    *
    * The second parameter allows a slash to be added to the end of
    * the item, in the case of a path.
    *
    * @access   public
    * @param    string    the config item name
    * @param    bool
    * @return   string
    */
    public function slash_item($item)
    {
        if ( ! isset($this->config[$item]))
        {
            return FALSE;
        }

        $pref = $this->config[$item];

        if ($pref != '' AND substr($pref, -1) != '/')
        {    
            $pref .= '/';
        }

        return $pref;
    }
      
    // --------------------------------------------------------------------

    /**
    * Site URL
    *
    * @access   public
    * @param    string    the URI string
    * @param    boolean   switch off suffix by manually
    * @return   string
    */
    public function site_url($uri = '', $suffix = TRUE)
    {
        if (is_array($uri))
        {
            $uri = implode('/', $uri);
        }
        
        if ($uri == '')
        {
            return $this->base_url() . $this->item('index_page');
        }
        else
        {
            $suffix = ($this->item('url_suffix') == FALSE OR $suffix == FALSE) ? '' : $this->item('url_suffix');
            
            return $this->base_url() . $this->slash_item('index_page'). trim($uri, '/') . $suffix;
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Base URL
    * Returns base_url
    * 
    * @access public
    * @param string $uri
    * @return string
    */
    public function base_url($uri = '')
    {
        return $this->slash_item('base_url').ltrim($uri,'/');
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Public URL (Get the url for static media files)
    *
    * @author   Ersin Guvenc
    * @access   public
    * @param    string uri
    * @param    bool $no_slash  no trailing slashes
    * @return   string
    */
    public function public_url($uri = '', $no_folder = FALSE, $no_ext_uri_slash = FALSE)
    {
        $extra_uri     = (trim($uri, '/') != '') ? trim($uri, '/').'/' : '';
        $public_folder = ($no_folder) ? '' : trim($this->item('public_folder'), '/').'/';
        
        if($no_ext_uri_slash)
        {
            $extra_uri = trim($extra_uri, '/');
        }
        
        return $this->slash_item('public_url') .$public_folder. $extra_uri;
    }
    
    // --------------------------------------------------------------------

    /**
    * Base Folder
    *
    * @access    public
    * @return    string
    */
    public function base_folder()
    {
        $x = explode("/", preg_replace("|/*(.+?)/*$|", "\\1", trim(BASE, DS)));
        
        return $this->base_url() . end($x).'/';
    }
      
    // --------------------------------------------------------------------
    
    /**
    * Set a config file item
    *
    * @access   public
    * @param    string    the config item key
    * @param    string    the config item value
    * @return   void
    */
    public function set_item($item, $value)
    {
        return $this->set($item, $value);
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set a config file item
    * alias of config_item we will deprecicate it later.
    *
    * @access   public
    * @param    string    the config item key
    * @param    string    the config item value
    * @return   void
    */
    public function set($item, $value)
    {
        $this->config[$item] = $value;
    }

}

// END Config Class

/* End of file Config.php */
/* Location: ./obullo/libraries/Config.php */