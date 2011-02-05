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
if( ! function_exists('driver_lib')) 
{
    function driver_lib($folder = '', $class = '', $options = array())
    {
        $classname = ucfirst(strtolower($class));                            
        
        $classfile = BASE .'libraries'. DS .'drivers'. DS .$folder. DS .$classname. EXT;

        if ( ! class_exists($classname)) 
        {
            include_once $classfile;
        }
                
        $classname = 'OB_'.$classname;
        $prefix    = config_item('subclass_prefix');  // MY_
        
        if(file_exists(APP .'libraries'. DS .$prefix. $class. EXT))  // Application extend support
        {
            if ( ! class_exists($classname)) 
            {
                require(APP .'libraries'. DS .$prefix. $class. EXT);
            }
            
            $classname = $prefix. $class;

            profiler_set('libraries', 'php_'. $class . '_driver', $prefix . $class);
        } 
        
        if (class_exists($classname))   // If the class exists, return a new instance of it.  
        {               
            if(count($options) > 0)
            {
                $pager = new $classname($options);
            } 
            else 
            {
                $pager = new $classname(); 
            }

            return $pager;
        }

        return NULL;
    }
}

/* End of file driver.php */
/* Location: ./obullo/helpers/driver.php */