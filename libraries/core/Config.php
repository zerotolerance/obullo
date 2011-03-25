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
 
Class ConfigException extends CommonException {}  

/**
 * Obullo Config Class

 * This class contains functions that enable config files to be managed
 *
 * @package     Obullo
 * @subpackage  Libraries
 * @category    Libraries
 * @author      Ersin Guvenc
 * @link        
 */
Class OB_Config
{    
    public $config          = array();
    public $is_loaded       = array();
    public $auto_base_url   = FALSE;
    public $auto_public_url = FALSE;
    
    /**
    * Constructor
    *
    * Sets the $config data from the primary config.php file as a class variable
    *
    * @access  public
    * @param   string   the config file name
    * @param   boolean  if configuration values should be loaded into their own section
    * @param   boolean  true if errors should just return false, false if an error message should be displayed
    * @return  boolean  if the file was successfully loaded or not
    */
    public function __construct()
    {
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
        
        $file = ($file_info['filename'] == '') ? 'config' : str_replace(EXT, '', $file_info['filename']);
    
        if (in_array($file, $this->is_loaded, TRUE))
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
    
        include($file_info['path'] .$file. EXT);

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

        $this->is_loaded[] = $file;
        profiler_set('config_files', $file, $file);
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
        if($extra_path != '')
        {
            $extra_path = str_replace('/', DS, trim($extra_path, '/')) . DS;
        }
        
        $file_url  = strtolower($file_url);

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

            $modulename = $GLOBALS['d'];
        }

        $sub_path   = '';
        if( count($paths) > 0)
        {
            $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
        }
        
        $path        = APP .'config'. DS .$sub_path .$extra_path;
        $module_path = MODULES .$modulename. DS .'config'. DS .$sub_path. $extra_path;
        
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
    * Set host based auto base url
    * 
    * @param    boolean  on / off
    * @return   void
    */
    public function auto_base_url($bool = TRUE)     // Obullo changes ..
    {
        $this->auto_base_url = $bool;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set host based auto public url
    * 
    * @param    boolean  on / off
    * @return   void
    */
    public function auto_public_url($bool = TRUE)   // Obullo changes ..
    {
        $this->auto_public_url = $bool;
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
    * Get the base url automatically.
    * 
    * @return    string
    */
    public function base_url()
    {
        if($this->auto_base_url)  // Obullo changes ..
        {
            $scrpt_name = i_server('SCRIPT_NAME');
            
            return str_replace(basename($scrpt_name), '', $scrpt_name);
        }
    
        return $this->slash_item('base_url');
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
        
        if($this->auto_public_url)    // Obullo changes ..
        {
            return $this->base_url() .$public_folder. $extra_uri;
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
        echo 'This function deprecated please use $this->config->set() function !';
    }
    
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