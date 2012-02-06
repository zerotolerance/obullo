<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2012.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo    
 * @author          obullo.com
 * @copyright       Obullo Team.
 * @since           Version 1.0
 * @filesource
 * @license
 */
 
define('OBULLO_VERSION', '1.0.1');
 
 /**
 * Controller Class.
 *
 * Main Controller class.
 *
 * @package         Obullo 
 * @subpackage      Obullo.core     
 * @category        Core
 * @version         0.2
 */

 /**
 * Main Controller Class.
 * The Core of Obullo.
 * 
 * @package         Obullo 
 * @subpackage      Obullo.core     
 * @category        Core
 * @version         0.1
 * @version         0.2 added extends App_controller
 * @version         0.3 @deprecated App_controller added Autoloader and Autorun funcs.
 */

Class Controller {

    private static $instance;

    public function __construct()       
    {   
        self::$instance = &$this;

        $this->config = lib('ob/Config');
        $this->router = lib('ob/Router');
        $this->uri    = lib('ob/URI');
        $this->output = lib('ob/Output');
        
        // NOTE: Autoload, autorun and constants should be load at Controller
        // level because of Hmvc library do request to Controller file.
        
        $sub_module = $this->uri->fetch_sub_module();
        $module     = $this->router->fetch_directory();
        
        // SUBMODULE
        // --------------------------------------------------------------------
        
        if( $sub_module != '' AND is_dir(MODULES .'sub.'.$sub_module. DS .'config'))
        { 
            // CONFIG FILES
            // -------------------------------------------------------------------- 
            if(file_exists(MODULES .'sub.'.$sub_module. DS .'config'. DS .'config'. EXT))
            {
                loader::config('../sub.'.$sub_module.'/config');
            }
            
            // CONSTANTS
            // -------------------------------------------------------------------- 
            if(file_exists(MODULES .'sub.'.$sub_module. DS.'config'. DS .'constants'. EXT))
            {
                get_static('constants', '', MODULES .'sub.'.$sub_module. DS.'config');
            }
            
        }

        // MODULE
        // --------------------------------------------------------------------

        // CONFIG FILES
        // -------------------------------------------------------------------- 
        
        if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'config'. DS .'config'. EXT))
        {
            loader::config('config');
        }
        
        // CONSTANTS
        // -------------------------------------------------------------------- 
        
        if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'config'. DS .'constants'. EXT))
        {
            get_static('constants', '', MODULES .$GLOBALS['sub_path'].$module. DS .'config');
        }  
        
        // AUTOLOADERS
        // -------------------------------------------------------------------- 
        
        $autoload = __merge_autoloaders($module, 'autoload', '', 'Autoloaders', $sub_module);

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
                            throw new Exception("Autoload function error, autoload array must be associative. 
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
        
        // AUTORUNS
        // -------------------------------------------------------------------- 

        $autorun = __merge_autoloaders($module, 'autorun', '', 'Autorun', $sub_module);
        
        if(isset($autorun['function']))
        {
            if(count($autorun['function']) > 0)
            {
                foreach(array_reverse($autorun['function']) as $function => $arguments)
                {   
                    if( ! function_exists($function))
                    {
                        throw new Exception('The autorun function '. $function . ' not found, please define it in 
                            APP/config/autoload.php or MODULES/sub.module/config/ or MODULES/'.$module.'/config/');
                    }

                    call_user_func_array($function, $arguments);

                    profiler_set('autorun', $function, $arguments);
                }
            }
        }
    }

    // -------------------------------------------------------------------- 
    
    /**
    * Fetch or Set Controller Instance
    * 
    * @param type $new_instance
    * @return type 
    */
    public static function _instance($new_instance = '')
    {   
        if(is_object($new_instance))
        {
            self::$instance = $new_instance;
        }    

        return self::$instance;
    } 
    
}

// -------------------------------------------------------------------- 

/**
* Grab Obullo Super Object
* 
* A Pretty handy function this();
* We use "this()" function if not available $this anywhere.
*
* @param object $new_istance  
*/
function this($new_instance = '') 
{ 
    if(is_object($new_instance))  // fixed HMVC object type of integer bug in php 5.1.6
    {
        Controller::_instance($new_instance);
    }
    
    return Controller::_instance(); 
}

// -------------------------------------------------------------------- 

/**
* Merge Modules autoload, autorun and constant
* file variables to application.
* 
* @param string $module
* @param string $file
* @param string $type
* @return array
*/
function __merge_autoloaders($module, $file = 'autoload', $var = '', $type = 'Autoloaders', $sub_module = '')
{   
    $app_vars = get_static($file, $var, APP .'config');
           
    if($sub_module != '') // Sub Module
    { 
        if( file_exists(MODULES .'sub.'.$sub_module. DS .'config'. DS .$file. EXT))
        {
            $sub_module_vars = get_static($file, $var, MODULES .'sub.'.$sub_module. DS .'config');
            
            foreach($app_vars as $key => $array)
            {
                $values[$key] = array_merge($sub_module_vars[$key], $array); 
            }
            
            log_me('debug', '[ '.ucfirst($sub_module).' ]: Sub-Module and Application '.$type.' Merged', false, true);
        }
        
        if( ! file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'config'. DS .$file. EXT))
        {
            return $values;
        }
    }
    
    if( file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'config'. DS .$file. EXT))
    {           
        $module_vars = get_static($file, $var, MODULES .$GLOBALS['sub_path'].$module. DS .'config');
       
        if(isset($sub_module_vars))  // Merge Sub-Module and Application Variables
        {
            unset($app_vars);
            $app_vars = $values;
        }
        
        foreach($app_vars as $key => $array)
        {   
            $values[$key] = array_merge($module_vars[$key], $array);
        }
        
        log_me('debug', '[ '.ucfirst($module).' ]: Module and Application '.$type.' Merged', false, true);
    } 
    else 
    {  
        $values = get_static($file, $var, APP .'config');

        log_me('debug', 'Application '.$type.' Initialized');
    }
    
    return $values;
}

// END Controller Class

/* End of file Controller.php */
/* Location: ./obullo/core/Controller.php */