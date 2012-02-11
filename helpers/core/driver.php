<?php
defined('BASE') or exit('Access Denied!'); 

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
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
 * @author      Obullo Team
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
        static $overriden_objects = array();
        
        $Class = ucfirst(strtolower($class));                           
        
        $classfile = BASE .'libraries'. DS .'drivers'. DS .$folder. DS .$Class. EXT;

        if ( ! class_exists($Class)) 
        {
            include_once($classfile);
        }
        
        $classname = 'OB_'.$Class;
        $prefix    = config_item('subclass_prefix');  // MY_
        $module    = lib('ob/Router')->fetch_directory();
        
        // Extension Support
        // --------------------------------------------------------------------
        
        if( ! isset($overriden_objects[$Class]))    // Check before we override it ..
        {
            $module_xml = lib('ob/Module'); // parse module.xml 

            if($module_xml->xml() != FALSE)
            {
                $extensions = $module_xml->get_extensions();

                if(count($extensions) > 0)   // Parse Extensions
                {
                    foreach($extensions as $ext_name => $extension)
                    { 
                        $attr = $extension['attributes'];
                        
                        if($attr['enabled'] == 'yes')
                        {
                            if(isset($extension['override']['libraries']))
                            {
                                foreach($extension['override']['libraries'] as $library)
                                {
                                    if( ! isset($overriden_objects[$library]))  // Singleton
                                    {
                                        if($Class == $library) // Do file_exist for defined library.
                                        {
                                            if(file_exists($attr['root'] .$ext_name. DS .'libraries'. DS .'drivers'. DS .$folder. DS .$prefix. $Class. EXT))  
                                            {
                                                require($attr['root'] .$ext_name. DS .'libraries'. DS .'drivers'. DS .$folder. DS .$prefix. $Class. EXT);
                                                
                                                $classname = $prefix. $Class;

                                                profiler_set('ob_libraries', 'php_'. $Class . '_overridden', $prefix . $Class);

                                                $overriden_objects[$library] = $library;
                                            }
                                        }
                                    }
                                }   
                            }
                        }
                    }
                }
            }
        }
        
        // Extension Support End
        // --------------------------------------------------------------------
        
        if( ! isset($overriden_objects[$Class]))    // Check before we override it ..
        {
            // Modules extend support
            if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'libraries'. DS .'drivers'. DS .$folder. DS .$prefix. $Class. EXT))  
            {
                if ( ! class_exists($prefix. $Class)) 
                {
                    require(MODULES .$GLOBALS['sub_path'].$module. DS .'libraries'. DS .'drivers'. DS .$folder. DS .$prefix. $Class. EXT);
                }

                $overriden_objects[$Class] = $Class;

                $classname = $prefix. $Class;

                profiler_set('ob_libraries', 'php_'. $Class . '_driver', $prefix . $Class);
            }
            elseif(file_exists(APP .'libraries'. DS .'drivers'. DS .$folder. DS .$prefix. $Class. EXT))  // Application extend support
            {
                if ( ! class_exists($prefix. $Class)) 
                {
                    require(APP .'libraries'. DS .'drivers'. DS .$folder. DS .$prefix. $Class. EXT);
                }

                $overriden_objects[$Class] = $Class;

                $classname = $prefix. $Class;

                profiler_set('ob_libraries', 'php_'. $Class . '_driver', $prefix . $Class);
            }
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

// --------------------------------------------------------------------

/**
 * Get helper driver file
 * 
 * @param type $folder
 * @param type $helpername
 * @param type $options 
 */
if( ! function_exists('helper_driver')) 
{
    function helper_driver($folder = '', $helpername = '', $options = array())
    {
        static $overridden_helpers = array();
        
        $prefix = config_item('subhelper_prefix');
        $module = lib('ob/Router')->fetch_directory();
        
        if( ! isset($overriden_helpers[$helpername]))
        {
            $module_xml = lib('ob/Module'); // parse module.xml 

            if($module_xml->xml() != FALSE)
            {
                $extensions = $module_xml->get_extensions();

                if(count($extensions) > 0)   // Parse Extensions
                {
                    foreach($extensions as $ext_name => $extension)
                    { 
                        $attr = $extension['attributes'];

                        if($attr['enabled'] == 'yes')
                        {
                            if(isset($extension['override']['helpers']))
                            {
                                foreach($extension['override']['helpers'] as $helper_item)
                                {               
                                    if( ! isset($overriden_helpers[$helper_item]))  // Singleton
                                    {
                                        if($helpername == $helper_item) // Do file_exist for defined helper.
                                        {    
                                            if(file_exists($attr['root'] .$ext_name. DS .'helpers'. DS .'drivers'. DS .$folder. DS .$prefix. $helpername. EXT))  
                                            {
                                                include($attr['root'] .$ext_name. DS .'helpers'. DS .'drivers'. DS .$folder. DS .$prefix. $helpername. EXT); 

                                                loader::$_base_helpers[$prefix .$helpername] = $prefix .$helpername;

                                                $overriden_helpers[$helper_item] = $helper_item;
                                            } 
                                        } 
                                    }
                                }   
                            }
                        }
                    }
                }
            }
        }  
        
        if( ! isset($overriden_helpers[$helpername]))
        {
            // Modules extend support
            if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'helpers'. DS .'drivers'. DS .$folder. DS .$prefix. $helpername. EXT))  
            {
                include(MODULES .$GLOBALS['sub_path'].$module. DS .'helpers'. DS .'drivers'. DS .$folder. DS .$prefix. $helpername. EXT);
                
                loader::$_base_helpers[$prefix .$helpername] = $prefix .$helpername;
            }
            elseif(file_exists(APP .'helpers'. DS .'drivers'. DS .$folder. DS .$prefix. $helpername. EXT))
            {
                include(APP .'helpers'. DS .'drivers'. DS .$folder. DS .$prefix. $helpername. EXT);
                
                loader::$_base_helpers[$prefix .$helpername] = $prefix .$helpername;
            }
        }
        
        include(BASE .'helpers'. DS .'drivers'. DS .$folder. DS .$helpername. EXT); // Include Session Driver
        
        loader::$_base_helpers[$helpername] = $helpername;
    }
}

/* End of file driver.php */
/* Location: ./obullo/helpers/driver.php */