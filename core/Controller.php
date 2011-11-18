<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2010.
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
 * Obullo Controllers 2010  
 * 
 * @package         Obullo   
 * @subpackage      Core.controller 
 * @category        Controller
 * 
 * @version 0.1 removed Obullo.php and moved all contents to Controller.php.
 * @version 0.2 depreciated old global controllers functionality, deleted parse_parents() func.
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
 * @version         0.1
 * @version         0.2 added extends App_controller
 * @version         0.3 @deprecated App_controller added autoloader
 */

 /**
 * Controller Class.
 *
 * Main Controller class.
 *
 * @package         Obullo 
 * @subpackage      Obullo.core     
 * @category        Core
 * @version         0.1
 * @version         0.2 added extends App_controller
 * @version         0.3 @deprecated App_controller added autoloader
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

        //---------- AUTOLOAD ------------//        

        if(file_exists(MODULES .$this->router->fetch_directory(). DS .'config'. DS .'autoload'. EXT))
        {
            log_me('debug', ucfirst($this->router->fetch_directory()).' Module Autoloader Initialized');

            $autoload = get_static('autoload', '', MODULES .$this->router->fetch_directory(). DS .'config');
        } 
        else 
        {
            log_me('debug', 'Application Autoloader Initialized');

            $autoload = get_static('autoload', '', APP .'config');
        }

        if(isset($autoload))
        {
            foreach(array_keys($autoload) as $key)
            {
                if(count($autoload[$key]) > 0)
                {
                    foreach($autoload[$key] as $file)
                    {
                        if(is_array($file))
                        {
                           foreach($file as $filename => $params)
                           {
                               loader::$key($filename, $params); 
                           }
                           
                        }
                        else
                        {
                            loader::$key($file);
                        }
                    }
                }
            }
        }
        
        //---------- AUTORUN ------------//  

        if(file_exists(MODULES .$this->router->fetch_directory(). DS .'config'. DS .'autorun'. EXT))
        {
            log_me('debug', ucfirst($this->router->fetch_directory()).' Module Autorun Initialized');

            $autorun = get_static('autorun', '', MODULES .$this->router->fetch_directory(). DS .'config');
        } 
        else 
        {
            log_me('debug', 'Application Autorun Initialized');

            $autorun = get_static('autorun', '', APP .'config');
        }
        
        if(isset($autorun['function']))
        {
            if(count($autorun['function']) > 0)
            {
                foreach($autorun['function'] as $function => $arguments)
                {
                     if( ! function_exists($function))
                     {
                         show_error('The autoload function '. $function . ' not found, please define it in APP/config/autoload.php or MODULES/module/config/autoload.php');
                     }
                    
                     call_user_func_array($function, $arguments);
                }
            }
        }
    }

    public static function _instance($new_instance = '')
    {   
        if(is_object($new_instance))
        {
            self::$instance = $new_instance;
        }    

        return self::$instance;
    } 
}

/**
* @author  Ersin Guvenc
* 
* A Pretty handy function this();
* We use "this()" function if not available $this anywhere.
*/
function this($new_instance = '') 
{ 
    if(is_object($new_instance))  // fixed HMVC object type of integer bug in php 5.1.6
    {
        Controller::_instance($new_instance);
    }
    
    return Controller::_instance(); 
}

// END Controller Class

/* End of file Controller.php */
/* Location: ./obullo/core/Controller.php */