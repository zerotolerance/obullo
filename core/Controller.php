<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2011.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         obullo    
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2009.
 * @since           Version 1.0
 * @filesource
 * @license
 */
 
 /**
 * Obullo Controllers 2010 - 2011
 * 
 * @package         Obullo   
 * @subpackage      Core.controller 
 * @category        Controller
 * 
 * @version 0.1 removed Obullo.php and moved all contents to Controller.php.
 * @version 0.2 @deprecated global controllers functionality, deleted parse_parents() func.
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
 * @version         0.3 @deprecated App_controller added autoloader and autorun func.
 */

Class Controller {

    private static $instance;

    public function __construct()       
    {   
        self::$instance = &$this;

        $this->config = core_class('Config');
        $this->router = core_class('Router');
        $this->uri    = core_class('URI');
        $this->output = core_class('Output');
        
        // NOTE: Autoload, autorun and constant functions should be load at Controller
        // level because of Hmvc library do request to Controller file.

        $module = $this->router->fetch_directory();
        
        // CONFIG FILE
        // -------------------------------------------------------------------- 
        
        if(file_exists(MODULES .$module. DS .'config'. DS .'config'. EXT))
        {
            loader::config('config');
        }
        
        // CONSTANTS
        // -------------------------------------------------------------------- 

        if(file_exists(MODULES .$module. DS .'config'. DS .'constants'. EXT))
        {
            get_static('constants', '', MODULES .$module. DS .'config');
        }  
        
        // AUTOLOADERS
        // -------------------------------------------------------------------- 
        
        $autoload = __merge_autoloaders($module, 'autoload', '', 'Autoloaders');
        
        if(isset($autoload))
        {
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
        }
        
        // AUTORUNS
        // -------------------------------------------------------------------- 

        $autorun = __merge_autoloaders($module, 'autorun', '', 'Autorun');

        if(isset($autorun['function']))
        {
            if(count($autorun['function']) > 0)
            {
                foreach($autorun['function'] as $function => $arguments)
                {
                     if( ! function_exists($function))
                     {
                         throw new Exception('The autoload function '. $function . ' not found, please define it in APP/config/autoload.php or MODULES/'.$module.'/config/autoload.php');
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
function __merge_autoloaders($module, $file = 'autoload', $var = '', $type = 'Autoloaders')
{
    if(file_exists(MODULES .$module. DS .'config'. DS .$file. EXT))
    {
        log_me('debug', '[ '.ucfirst($module).' ] Module '.$type.' Initialized');
        
        $module_vars = get_static($file, $var, MODULES .$module. DS .'config');
        $app_vars    = get_static($file, $var, APP .'config');

        foreach($app_vars as $key => $array)
        {
            $values[$key] = array_merge($module_vars[$key], $array);
        }

        log_me('debug', '[ '.ucfirst($module).' ] Module and Application '.$type.' Merged');
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