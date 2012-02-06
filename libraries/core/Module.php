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
 * Get the extension_path which is
 * defined in module.xml configuration file.
 * 
 * @param string $name extension name
 * @param string $attribute attribute key
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

// ------------------------------------------------------------------------


Class OB_Module {

    public $xml        = NULL; // xml object
    public $extensions = array();

    /**
     * Constructor
     *
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
        
        $module_xml = $this->_get_module_xml();

        if($module_xml != FALSE)
        {
            $this->xml = simplexml_load_file($module_xml['file']);
        }
        
        log_me('debug', "Module Class Initialized", false, true); // core level log
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
        $sub_module = lib('ob/URI')->fetch_sub_module();
        
        if($sub_module != '')
        {
            if(file_exists(MODULES .'sub.'.$sub_module. DS .SUB_MODULES .'module.xml'))  // If not found Check SUB MODULES root
            {
                return array('file' => MODULES .'sub.'.$sub_module. DS .SUB_MODULES .'module.xml');
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