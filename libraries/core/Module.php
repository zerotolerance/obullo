<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2012.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo
 * @author          obullo.com
 * @copyright       obullo.com (c) 2012.
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Module Class
 *
 * Parse module.xml file and store items
 * to object.
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Libraries
 * @author        Obullo Team
 * @link
 */

// ------------------------------------------------------------------------

/**
 * Get the extension attributes which is
 * defined in module.xml configuration file.
 * 
 * @param string $attribute attribute key
 * @param string $name extension name
 * @return object
 */
if ( ! function_exists('extension'))
{
    function extension($attribute = '', $name = '')
    {   
        $module_xml = lib('ob/Module');
        
        if($module_xml->get_attribute($name, $attribute) == FALSE)
        {
            return FALSE;
        }
        
        return $module_xml->get_attribute($name, $attribute);
    }
}

Class ModuleException extends CommonException {}

// ------------------------------------------------------------------------


Class OB_Module {

    public $module     = '';
    public $sub_module = '';
    public $xml        = NULL; // xml object
    public $extensions = array();

    /**
     * Constructor
     * Sets the "module.xml" file to object.
     *
     * @version   0.1
     * @access    public
     * @return    void
     */
    public function __construct()
    {
        // Warning :
        // 
        // Don't load any library in this Class because of Obullo use 
        // the Module Class at Bootstrap loading level. When you try load any library
        // you will get a Fatal Error.
        
        $module_xml = $this->_get_module_xml(); // Init Extension

        if($module_xml != FALSE)
        {
            $this->xml = simplexml_load_file($module_xml['file']);
        }
        
        log_me('debug', "Module Class Initialized", false, true); // core level log
    }

    // --------------------------------------------------------------------

    /**
    * Start the Initialization
    * 
    * @return void
    */
    public function init()
    {
        $this->_init_sub_module();  // Init Submodule
        $this->_init_module();      // Init Module
        $this->_init_autoloaders(); // Init Obullo Autoloader files
        $this->_init_autoruns();    // Init Obullo Autorun files
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Initalize to possible sub.module
    * 
    * @access private
    * @return void
    */
    public function _init_sub_module()
    {
        // Sub Module
        // ----------------------------------------------------
        
        $this->sub_module = lib('ob/URI')->fetch_sub_module();
        
        // ----------------------------------------------------
        
        if( $this->sub_module != '' AND is_dir(MODULES .'sub.'.$this->sub_module. DS .'config'))
        { 
            // Config Files
            // ------------------------------------------------
            if(file_exists(MODULES .'sub.'.$this->sub_module. DS .'config'. DS .'config'. EXT))
            {
                lib('ob/Config')->load('../sub.'.$this->sub_module.'/config');
            }
            
            // Constants
            // ------------------------------------------------
            if(file_exists(MODULES .'sub.'.$this->sub_module. DS.'config'. DS .'constants'. EXT))
            {
                get_static('constants', '', MODULES .'sub.'.$this->sub_module. DS.'config');
            }
        }
    }
    
    // --------------------------------------------------------------------
   
    /**
    * Initalize to module
    * 
    * @access private
    * @return void
    */
    public function _init_module()
    {
        // Module
        // ----------------------------------------------------
        
        $this->module = lib('ob/Router')->fetch_directory();
        
        // ----------------------------------------------------
        
        // Config Files
        // ----------------------------------------------------
        
        if(file_exists(MODULES .$GLOBALS['sub_path'].$this->module. DS .'config'. DS .'config'. EXT))
        {
            lib('ob/Config')->load('config');
        }
        
        // Constants
        // ----------------------------------------------------
        
        if(file_exists(MODULES .$GLOBALS['sub_path'].$this->module. DS .'config'. DS .'constants'. EXT))
        {
            get_static('constants', '', MODULES .$GLOBALS['sub_path'].$this->module. DS .'config');
        }  
    }

    // --------------------------------------------------------------------
    
    /**
    * Initalize to Obullo autoloaders and
    * merge autoload, autorun file variables to application module.
    * 
    * @access private
    * @return void
    */
    public function _init_autoloaders()
    {
        $autoload = $this->_merge_autoloaders('autoload', '', 'Autoloaders');

        if(is_array($autoload))
        {
            loader::helper('ob/array');
            
            foreach(array_keys($autoload) as $key)
            {
                if(count($autoload[$key]) > 0)
                {
                    if( ! is_assoc_array($autoload[$key]))
                    {
                        show_error('Please redefine your '.$key.' autoload variables, they must be associative array !');
                    }
                    
                    foreach($autoload[$key] as $filename => $args)
                    {
                        if( ! is_string($filename))
                        {
                            throw new ModuleException("Autoload function error, autoload array must be associative. 
                                An example configuration <b>\$autoload['helper'] = array('ob/session' => '', 'ob/form' => '')</b>");
                        }

                        if(is_array($args)) // if arguments exists
                        { 
                            switch($key)
                            {
                                case ($key == 'config' || $key == 'lang' || $key == 'lib' || $key == 'model'):
                                    
                                    $third_param = isset($args[1]) ? $args[1] : FALSE;
                                    
                                    loader::$key($filename, $args[0], $third_param);
                                    break;

                                case 'helper':
                                    
                                    loader::$key($filename, $args[0]);
                                    break;
                            }
                        }
                        else
                        {
                            loader::$key($filename);
                        }
                    }
                }
            }
            
            profiler_set('autoloads', 'autoloads', $autoload);
        }
    }
    
    // -------------------------------------------------------------------- 

    /**
    * Initalize to Obullo autoloaders and
    * merge autoload, autorun file variables to application module.
    * 
    * @access private
    * @return void
    */
    public function _init_autoruns()
    {
        $autorun = $this->_merge_autoloaders('autorun', '', 'Autorun');
        
        if(isset($autorun['function']))
        {
            if(count($autorun['function']) > 0)
            {
                foreach(array_reverse($autorun['function']) as $function => $arguments)
                {   
                    if( ! function_exists($function))
                    {
                        throw new ModuleException('The autorun function '. $function . ' not found, please define it in 
                            APP/config/autoload.php or MODULES/sub.module/config/ or MODULES/'.$this->module.'/config/');
                    }

                    call_user_func_array($function, $arguments);   // Run autorun function.

                    profiler_set('autorun', $function, $arguments);
                }
            }
        }  
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Merge Modules autoload, autorun ..
    * file variables to application module.
    * 
    * @access private
    * @param string $file
    * @param string $var
    * @param string $type
    * @return array
    */
    public function _merge_autoloaders($file = 'autoload', $var = '', $type = 'Autoloaders')
    {   
        $app_vars = get_static($file, $var, APP .'config');

        if($this->sub_module != '') // Sub Module
        { 
            if( file_exists(MODULES .'sub.'.$this->sub_module. DS .'config'. DS .$file. EXT))
            {
                $sub_module_vars = get_static($file, $var, MODULES .'sub.'.$this->sub_module. DS .'config');

                foreach($app_vars as $key => $array)
                {
                    $values[$key] = array_merge($sub_module_vars[$key], $array); 
                }

                log_me('debug', '[ '.ucfirst($this->sub_module).' ]: Sub-Module and Application '.$type.' Merged', false, true);
            }

            if( ! file_exists(MODULES .$GLOBALS['sub_path'].$this->module. DS .'config'. DS .$file. EXT))
            {
                return $values;
            }
        }

        if( file_exists(MODULES .$GLOBALS['sub_path'].$this->module. DS .'config'. DS .$file. EXT))
        {           
            $module_vars = get_static($file, $var, MODULES .$GLOBALS['sub_path'].$this->module. DS .'config');

            if(isset($sub_module_vars))  // Merge Sub-Module and Application Variables
            {
                unset($app_vars);
                $app_vars = $values;
            }

            foreach($app_vars as $key => $array)
            {   
                $values[$key] = array_merge($module_vars[$key], $array);
            }

            log_me('debug', '[ '.ucfirst($this->module).' ]: Module and Application '.$type.' Merged', false, true);
        } 
        else 
        {  
            $values = get_static($file, $var, APP .'config');

            log_me('debug', 'Application '.$type.' Initialized');
        }

        return $values;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Find "module.xml" file.
    * 
    * Get module XML Configuration file which is
    * located in /modules/ or /modules/sub.module/modules/ path
    * like a ".htaccess" file.
    * 
    * @return array | boolean
    */
    public function _get_module_xml()
    {   
        $this->sub_module = lib('ob/URI')->fetch_sub_module();
        
        if($this->sub_module != '')
        {
            if(file_exists(MODULES .'sub.'.$this->sub_module. DS .SUB_MODULES .'module.xml'))  // If not found Check SUB MODULES root
            {
                return array('file' => MODULES .'sub.'.$this->sub_module. DS .SUB_MODULES .'module.xml');
            }
        }
        
        if(file_exists(MODULES .'module.xml'))  // If not found Check MODULES root
        {
            return array('file' => MODULES .'module.xml');
        }

        return FALSE;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Return parsed xml file object that 
    * we get it before via simplexml_parse_file();
    * function.
    * 
    * @return xml object
    */
    public function xml()
    {
        return (is_object($this->xml)) ? $this->xml : FALSE;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Get the defined extensions in module.xml file.
    * 
    * @return array | boolean
    */
    public function get_extensions()
    {
        if($this->xml() != FALSE)   // Parse Extensions
        {
            $i = 0;
            foreach($this->xml->extension as $x)
            { 
                $extension  = $this->xml->extension[$i]->attributes();
                $ext_name   = trim((string)$extension->name);
                $is_enabled = strtolower(trim((string)$extension->enabled));
                $env        = (isset($extension->env)) ? strtoupper(trim((string)$extension->env)) : '';
                $path       = trim((string)$extension->path, '/');
                
                if(empty($path))
                {
                    $root = MODULES;
                }
                else
                {
                    $root = MODULES . str_replace('modules', '', $path). DS .SUB_MODULES;
                }
                
                if($env != '')
                {
                    $ENV = array('DEV','TEST','DEBUG','LIVE');  // Enable / Disable for different environments.

                    if(strpos($env, ',') > 0)
                    {
                        $ENV = explode(',', $env);
                    }
                    else
                    {
                        $ENV = array($env);
                    }
                    
                    if(in_array(ENV, $ENV))
                    {   
                        $is_enabled = ($is_enabled != 'no') ? 'yes' : 'no';
                    }
                    else
                    {
                        $is_enabled = 'no';
                    }
                }
                
                $this->extensions[$ext_name]['attributes']['enabled'] = $is_enabled;
                $this->extensions[$ext_name]['attributes']['env']     = $env;
                $this->extensions[$ext_name]['attributes']['path']    = $path;
                $this->extensions[$ext_name]['attributes']['root']    = $root;
                
                if(isset($this->xml->extension[$i]->override))
                {
                    $override = $this->xml->extension[$i]->override;
                    ++$i;
                    
                    if(isset($override->library))
                    {
                        foreach($override->library as $library)
                        {
                            $this->extensions[$ext_name]['override']['libraries'][] = ucfirst(strtolower(trim((string)$library)));
                        }
                    }
                    
                    if(isset($override->helper))
                    {
                        foreach($override->helper as $helper)
                        {
                            $this->extensions[$ext_name]['override']['helpers'][] = strtolower(trim((string)$helper));
                        }        
                    }
                }
            }
        
            return $this->extensions;
        }
        
        return FALSE;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Get current extension attribute.
    * 
    * @param string $extension name of the extension
    * @return string loading path of the extension
    */
    public function get_attribute($extension, $key)
    {
        if(isset($this->extensions[$extension]['attributes'][$key]))
        {
            return $this->extensions[$extension]['attributes'][$key];
        }
        
        return FALSE;
    }
    
}


/* End of file Module.php */
/* Location: ./obullo/libraries/core/Module.php */