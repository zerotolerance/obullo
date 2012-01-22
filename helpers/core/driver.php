<?php
defined('BASE') or exit('Access Denied!'); 

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @license         public
 * @since           Version 1.0
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Obullo Driver Helpers
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */

// ------------------------------------------------------------------------

/**
* Get library driver file
* 
* @param  string $folder
* @param  string $class
* @param  array  $options construct parameters
* @return string
*/
if( ! function_exists('lib_driver')) 
{
    function lib_driver($folder = '', $class = '', $options = array())
    {
        $classname = ucfirst(strtolower($class));                            
        
        $classfile = BASE .'libraries'. DS .'drivers'. DS .$folder. DS .$classname. EXT;

        if ( ! class_exists($classname)) 
        {
            include_once($classfile);
        }
        
        $classname = 'OB_'.$classname;
        $prefix    = config_item('subclass_prefix');  // MY_
        $module    = core_class('Router')->fetch_directory();
        
        // Modules extend support
        if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'libraries'. DS .'drivers'. DS .$prefix. $class. EXT))  
        {
            if ( ! class_exists($prefix. $class)) 
            {
                require(MODULES .$GLOBALS['sub_path'].$module. DS .'libraries'. DS .'drivers'. DS .$prefix. $class. EXT);
            }
            
            $classname = $prefix. $class;

            profiler_set('libraries', 'php_'. $class . '_driver', $prefix . $class);
        }
        elseif(file_exists(APP .'libraries'. DS .'drivers'. DS .$prefix. $class. EXT))  // Application extend support
        {
            if ( ! class_exists($prefix. $class)) 
            {
                require(APP .'libraries'. DS .'drivers'. DS .$prefix. $class. EXT);
            }
            
            $classname = $prefix. $class;

            profiler_set('libraries', 'php_'. $class . '_driver', $prefix . $class);
        } 
        
        if (class_exists($classname))   // If the class exists, return a new instance of it.  
        {               
            if(count($options) > 0)
            {
                $instance = new $classname($options);
            } 
            else 
            {
                $instance = new $classname(); 
            }

            return $instance;
        }

        return NULL;
    }
}


/* End of file driver.php */
/* Location: ./obullo/helpers/driver.php */