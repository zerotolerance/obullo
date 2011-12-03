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
 * @since           Version 1.0
 * @filesource
 * @license
 */

/**
* Common.php
*
* @version 1.0
* @version 1.1 added removed OB_Library:factory added
*              lib_factory function
* @version 1.2 removed lib_factory function
* @version 1.3 renamed base "libraries" folder as "base"
* @version 1.4 added $var  and config_name vars for get_config()
* @version 1.5 added PHP5 library interface class, added spl_autoload_register()
*              renamed register_static() function, added replace support ..
*/
                       
/**
* Register core libraries
* 
* @param string $realname
* @param boolean | object $new_object
* @param array $params_or_no_ins
*/
function core_class($realname, $new_object = NULL, $params_or_no_ins = '')
{
    static $new_objects = array();                
    
    $Class    = ucfirst($realname);
    $registry = OB_Registry::instance();
    
    // if we need to reset any registered object .. 
    // --------------------------------------------------------------------
    if(is_object($new_object))
    {
        $registry->unset_object($Class);
        $registry->set_object($Class, $new_object);
        
        return $new_object;
    }
    
    $getObject = $registry->get_object($Class);   
                                                   
    if ($getObject !== NULL)
    return $getObject;
                      
    if(file_exists(BASE .'libraries'. DS .'core'. DS .$Class. EXT))
    {
        if( ! isset($new_objects[$Class]) )  // check new object instance
        {
            require(BASE .'libraries'. DS .'core'. DS .$Class. EXT);
        }
        
        $classname = $Class;    // prepare classname

        if($params_or_no_ins === FALSE)
        {
            profiler_set('libraries', 'php_'.$Class.'_no_instantiate', $Class);
            return TRUE;
        }

        $classname = 'OB_'.$Class;
        $prefix    = config_item('subclass_prefix');  // MY_
         
        if(file_exists(APP .'libraries'. DS .$prefix. $Class. EXT))  // Application extend support
        {
            if( ! isset($new_objects[$Class]) )  // check new object instance
            {
                require(APP .'libraries'. DS .$prefix. $Class. EXT);
            }
            
            $classname = $prefix. $Class;

            profiler_set('libraries', 'php_'. $Class . '_overridden', $prefix . $Class);
        } 
        
        // __construct params support.
        // --------------------------------------------------------------------
        if($new_object == TRUE)
        {
            if(is_array($params_or_no_ins))  // construct support.
            {
                $Object = new $classname($params_or_no_ins);

            } else
            {
                $Object = new $classname();
            }
            
            $new_objects[$Class] = $Class;  // set new instance to static variable
        } 
        else 
        {
            if(is_array($params_or_no_ins)) // construct support.
            {
                $registry->set_object($Class, new $classname($params_or_no_ins));

            } else
            {
                $registry->set_object($Class, new $classname());
            }

            $Object = $registry->get_object($Class);
        }

        // return to singleton object.
        // --------------------------------------------------------------------
                      
        if(is_object($Object))
        return $Object;

    }

    return NULL;  // if register func return to null
                  // we will show a loader exception
}

// -------------------------------------------------------------------- 

/**
* load_class()
*
* Register base classes which start by OB_ prefix
*
* @access   private
* @param    string $class the class name being requested
* @param    array | bool $params_or_no_ins (__construct parameter ) | or | No Instantiate
* @version  0.1
* @version  0.2 removed OB_Library::factory()
*               added lib_factory() function
* @version  0.3 renamed base "libraries" folder as "base"
* @version  0.4 added extend to core libraries support
* @version  0.5 added $params_or_no_ins instantiate switch FALSE.
* @version  0.6 added $new_object instance param, added unset object.
* @version  0.7 added new instance support ( added $new_objects variable).
*
* @return   object  | NULL
*/
function load_class($realname, $new_object = NULL, $params_or_no_ins = '')
{
    static $new_objects       = array();               
    static $overriden_objects = array();
    
    // Sub path support
    // --------------------------------------------------------------------
    $sub_path = '';
    if(strpos($realname, '/') > 0)
    {
        $paths    = explode('/', $realname);
        $realname = array_pop($paths);         // get file name
        $sub_path = DS . implode(DS, $paths);  // build sub path  ( e.g ./drivers/pager/)
    }
    
    $Class    = ucfirst($realname);
    $registry = OB_Registry::instance();
    
    // if we need to reset any registered object .. 
    // --------------------------------------------------------------------
    if(is_object($new_object))
    {
        $registry->unset_object($Class);
        $registry->set_object($Class, $new_object);
        
        return $new_object;
    }

    $getObject = $registry->get_object($Class);
                                                   
    if ($getObject !== NULL)
    {
        return $getObject;
    }
                                                  
    if(file_exists(BASE .'libraries'. $sub_path . DS . $Class. EXT))
    {
        if( ! isset($new_objects[$Class]) )  // check new object instance
        {
            require(BASE .'libraries'. $sub_path . DS . $Class. EXT);
        }
        
        $classname = $Class;    // prepare classname

        if($params_or_no_ins === FALSE)
        {
            profiler_set('libraries', 'php_'.$Class.'_no_instantiate', $Class);
            return TRUE;
        }

        $classname   = 'OB_'.$Class;
        $prefix      = config_item('subclass_prefix');  // MY_
        $module      = core_class('Router')->fetch_directory();
        $extensions  = get_config('extensions');
        
        //------------------ OVERRIDE SUPPORT ------------------//
        
        if( ! isset($overriden_objects[$Class]))    // Check before we override it ..
        {
            $extension_lib_override = FALSE;
            
            if(is_array($extensions))
            {
                foreach($extensions as $name => $array)   // Extension Override Support
                {
                    foreach($array as $ext_name => $options)           // Parse values.
                    {
                        if(isset($options['lib_override']) AND is_array($options['lib_override']))
                        {
                            foreach($options['lib_override'] as $lib_override)
                            {
                                if($lib_override == $Class)
                                {
                                    $extension_lib_override = TRUE;
                                    $extension = $ext_name;
                                }
                            }
                        }                            
                    }
                }
            }                            

            if($extension_lib_override)
            {
                if(is_extension($extension, $module))  // if extension enabled .. 
                { 
                    $module = $extension;
                }
            }
            
            // Modules extend support
            if(file_exists(MODULES .$module. DS .'libraries'. $sub_path . DS .$prefix. $Class. EXT))  
            {
                if( ! isset($new_objects[$Class]) )  // check new object instance
                {
                    require(MODULES .$module. DS .'libraries'. $sub_path . DS .$prefix. $Class. EXT);
                }
                
                $classname = $prefix. $Class;
     
                profiler_set('libraries', 'php_'. $Class . '_overridden', $prefix . $Class);
                
                $overriden_objects[$Class] = $Class;
            }  
            elseif(file_exists(APP .'libraries'. $sub_path . DS .$prefix. $Class. EXT))  // Application extend support
            {
                if( ! isset($new_objects[$Class]) )  // check new object instance
                {
                    require(APP .'libraries'. $sub_path . DS .$prefix. $Class. EXT);
                }
                
                $classname = $prefix. $Class;

                profiler_set('libraries', 'php_'. $Class . '_overridden', $prefix . $Class);
                
                $overriden_objects[$Class] = $Class;
            }     
            
        }
        
        //------------------ END OVERRIDE SUPPORT ------------------// 
        
        // __construct params support.
        // --------------------------------------------------------------------
        if($new_object == TRUE)
        {
            if(is_array($params_or_no_ins))  // construct support.
            {
                $Object = new $classname($params_or_no_ins);

            } else
            {
                $Object = new $classname();
            }
            
            $new_objects[$Class] = $Class;  // set new instance to static variable
        } 
        else 
        {
            if(is_array($params_or_no_ins)) // construct support.
            {
                $registry->set_object($Class, new $classname($params_or_no_ins));

            } else
            {
                $registry->set_object($Class, new $classname());
            }

            $Object = $registry->get_object($Class);
        }

        // return to singleton object.
        // --------------------------------------------------------------------
                      
        if(is_object($Object))
        {
            return $Object;
        }
        
    }

    return NULL;  // if register func return to null
                  // we will show a loader exception
}

// --------------------------------------------------------------------

/**
* register_autoload();
* Autoload Just for php5 Libraries.
*
* @access   public
* @author   Ersin Guvenc
* @param    string $class name of the class.
*           You must provide real class name. (lowercase)
* @param    boolean $base base class or not
* @version  0.1
* @version  0.2 added base param
* @version  0.3 renamed base "libraries" folder as "base"
* @version  0.4 added php5 library support, added spl_autoload_register() func.
* @version  0.5 added replace and extend support
* @version  0.6 removed LoaderException to play nicely with other autoloaders.
*
* @return NULL | Exception
*/
function ob_autoload($real_name)
{
    if(class_exists($real_name))
    return;
    
    $module = core_class('Router')->fetch_directory();

    // Parents folder files: App_controller and Global Controllers
    // --------------------------------------------------------------------
    if(substr(strtolower($real_name), -11) == '_controller')
    {
        // If Global Controller file exist ..
        if(file_exists(APP .'parents'. DS .$real_name. EXT))
        {
            require(APP .'parents'. DS .$real_name. EXT);

            profiler_set('parents', $real_name, APP .'parents'. DS .$real_name. EXT);

            return;
        }

        // If Module Global Controller file exist ..
        if(file_exists(MODULES .$module. DS .'parents'. DS .$real_name. EXT))
        {            
            require(MODULES .$module. DS .'parents'. DS .$real_name. EXT);

            profiler_set('parents', $real_name, MODULES .$module. DS .'parents'. DS .$real_name. EXT);

            return;
        }
    }

    // Database files.
    // --------------------------------------------------------------------
    if(strpos($real_name, 'OB_DB') === 0)
    {
        require(BASE .'database'. DS .substr($real_name, 3). EXT);
        return;
    }

    if(strpos($real_name, 'Obullo_DB_Driver_') === 0)
    {
        $exp   = explode('_', $real_name);
        $class = strtolower(array_pop($exp));

        require(BASE .'database'. DS .'drivers'. DS .$class.'_driver'. EXT);
        return;
    }
    
    if($real_name == 'Model' OR $real_name == 'VM')
    {
        require(BASE .'core'. DS .$real_name. EXT);
        return;
    }

    $class = $real_name;

    // __autoload libraries load support.
    // --------------------------------------------------------------------
    if(file_exists(MODULES .$module. DS .'libraries'. DS .$class. EXT))
    {
        require(MODULES .$module. DS .'libraries'. DS .$class. EXT);

        profiler_set('libraries', 'module_'.$class.'_autoloaded', $class);
        return;
    }
    
    if(file_exists(APP .'libraries'. DS .$class. EXT))
    {    
        require(APP .'libraries'. DS .$class. EXT);

        profiler_set('libraries', $class.'_autoloaded', $class);
        return;
    }
    
    return;
}

spl_autoload_register('ob_autoload', true);

// --------------------------------------------------------------------

/**
* Obullo library loader
* 
* @param string $class
* @param array | false $params_or_no_instance
* @param true | object $new_object
*/
if( ! function_exists('lib'))
{
    function lib($class, $params_or_no_instance = '', $new_object = NULL)
    {
        if($new_object == NULL || $new_object == FALSE) 
        {   
            if(i_hmvc()) // We must create new instance for each hmvc requests
            {
                $new_object = TRUE;
            }
        }
        
        if(strpos($class, 'ob/') === 0)
        {               
           $class = strtolower(substr($class, 3));
        }
        
        // @todo if strpos('../', $class) so go loader::lib($class);
        
        return load_class(strtolower($class), $new_object, $params_or_no_instance);
    }
}

// --------------------------------------------------------------------

/**
* Loads the (static) configuration or language files.
*
* @access    private
* @author    Ersin Guvenc
* @param     string $filename file name
* @param     string $var variable of the file
* @param     string $folder folder of the file
* @version   0.1
* @version   0.2 added $config_name param
* @version   0.3 added $var variable
* @version   0.4 renamed function as get_static ,renamed $config_name as $filename, added $folder param
* @return    array
*/
function get_static($filename = 'config', $var = '', $folder = '')
{
    static $static = array();
    
    $key = $folder. DS .$filename. EXT;
    
    if ( ! isset($static[$key]))
    {
        if ( ! file_exists($folder. DS .$filename. EXT))
        {
            $error_msg = 'The static file '. $folder. DS .$filename. EXT .' does not exist.';
            
            log_me('debug', $error_msg);
            
            show_error($error_msg);
            
            return;
        }

        require($folder. DS .$filename. EXT);

        if($var == '') $var = &$filename;

        if($filename != 'autoload' AND $filename != 'constants')
        {
            if ( ! isset($$var) OR ! is_array($$var))
            {
                $error_msg = 'The static file '. $folder. DS .$filename. EXT .' file does not appear to be formatted correctly.';
                
                log_me('debug', $error_msg);
                
                show_error($error_msg);
            }
        }

        $static[$key] =& $$var;
     }

    return $static[$key];
}

// --------------------------------------------------------------------

/**
* Get config file.
*
* @access   public
* @param    string $filename
* @param    string $var
* @return   array
*/
function get_config($filename = 'config', $var = '')
{
    if($filename == 'database')
    {
        $module = (isset($GLOBALS['d'])) ? $GLOBALS['d'] : core_class('Router')->fetch_directory(); 
        
        if(file_exists(MODULES .$module. DS .'config'. DS .'database'.EXT)) // Module database support.
        {
            $mod_db = get_static($filename, $var, MODULES .$module. DS .'config');
            $app_db = get_static($filename, $var, APP .'config');
            
            return array_merge($app_db, $mod_db);
        }
    }
   
    return get_static($filename, $var, APP .'config');
}

// --------------------------------------------------------------------

/**
* Gets a config item
*
* @access    public
* @param     string $config_name file name
* @version   0.1
* @version   0.2 added $config_name var
*            multiple config support
* @return    mixed
*/
function config_item($item, $config_name = 'config')
{
    static $config_item = array();

    if ( ! isset($config_item[$item]))
    {
        $config_name = get_config($config_name);

        if ( ! isset($config_name[$item]))
        {
            return FALSE;
        }

        $config_item[$item] = $config_name[$item];
    }

    return $config_item[$item];
}

// --------------------------------------------------------------------

/**
* Gets a db configuration items
*
* @access    public
* @author    Ersin Guvenc
* @param     string $item
* @param     string $index 'default'
* @version   0.1
* @version   0.2 added multiple config fetch
* @return    mixed
*/
function db_item($item, $index = 'db')
{
    static $db_item = array();

    if ( ! isset($db_item[$index][$item]))
    {
        $database = get_config('database');
        
        if ( ! isset($database[$index][$item]))
        {
            return FALSE;
        }
        
        $db_item[$index][$item] = $database[$index][$item];
    }

    return $db_item[$index][$item];
}

// --------------------------------------------------------------------

/**
* Error Logging Interface
*
* We use this as a simple mechanism to access the logging
* class and send messages to be logged.
*
* @access    public
* @return    void
*/
function log_me($level = 'error', $message, $php_error = FALSE)
{
    if (config_item('log_threshold') == 0)
    {
        return;
    }
    
    log_write($level, $message, $php_error);

    return;
}

// --------------------------------------------------------------------

/**
* Tests for file writability
*
* is_writable() returns TRUE on Windows servers when you really can't write to
* the file, based on the read-only attribute.  is_writable() is also unreliable
* on Unix servers if safe_mode is on.
*
* @access    private
* @return    void
*/
function is_really_writable($file)
{
    // If we're on a Unix server with safe_mode off we call is_writable
    if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == FALSE)
    {
        return is_writable($file);
    }

    // For windows servers and safe_mode "on" installations we'll actually
    // write a file then read it.  Bah...
    if (is_dir($file))
    {
        $file = rtrim($file, '/').'/'.md5(rand(1,100));

        if (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
        {
            return FALSE;
        }

        fclose($fp);
        @chmod($file, DIR_WRITE_MODE);
        @unlink($file);
        return TRUE;
    }
    elseif (($fp = @fopen($file, FOPEN_WRITE_CREATE)) === FALSE)
    {
        return FALSE;
    }

    fclose($fp);
    return TRUE;
}

// --------------------------------------------------------------------

/**
* Determines if the current version of PHP is greater then the supplied value
*
* Since there are a few places where we conditionally test for PHP > 5
* we'll set a static variable.
*
* @access   public
* @param    string
* @return   bool
*/
function is_php($version = '5.0.0')
{
    static $_is_php;
    
    $version = (string)$version;

    if ( ! isset($_is_php[$version]))
    {
        $_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
    }

    return $_is_php[$version];
}

// --------------------------------------------------------------------  

/**
* Set data to profiler variable
*
* @param    string $type log type
* @param    string $key  log key
* @param    string $val  log val
*/
function profiler_set($type, $key, $val)
{
    load_class('Storage')->profiler_var[$type][$key] = $val;
}

// --------------------------------------------------------------------  

/**
* Get profiler data from profiler
* variable.
*
* @param    string $type log type
* @return   array
*/
function profiler_get($type)
{
    $_ob = load_class('Storage');
    
    if( isset($_ob->profiler_var[$type]))
    {
        return $_ob->profiler_var[$type];
    };

    return array();
}

// ------------------------------------------------------------------------

/**
* Set HTTP Status Header
*
* @access   public
* @param    int     the status code
* @param    string    
* @return   void
*/
function set_status_header($code = 200, $text = '')
{
    $stati = array(
                        200    => 'OK',
                        201    => 'Created',
                        202    => 'Accepted',
                        203    => 'Non-Authoritative Information',
                        204    => 'No Content',
                        205    => 'Reset Content',
                        206    => 'Partial Content',

                        300    => 'Multiple Choices',
                        301    => 'Moved Permanently',
                        302    => 'Found',
                        304    => 'Not Modified',
                        305    => 'Use Proxy',
                        307    => 'Temporary Redirect',

                        400    => 'Bad Request',
                        401    => 'Unauthorized',
                        403    => 'Forbidden',
                        404    => 'Not Found',
                        405    => 'Method Not Allowed',
                        406    => 'Not Acceptable',
                        407    => 'Proxy Authentication Required',
                        408    => 'Request Timeout',
                        409    => 'Conflict',
                        410    => 'Gone',
                        411    => 'Length Required',
                        412    => 'Precondition Failed',
                        413    => 'Request Entity Too Large',
                        414    => 'Request-URI Too Long',
                        415    => 'Unsupported Media Type',
                        416    => 'Requested Range Not Satisfiable',
                        417    => 'Expectation Failed',

                        500    => 'Internal Server Error',
                        501    => 'Not Implemented',
                        502    => 'Bad Gateway',
                        503    => 'Service Unavailable',
                        504    => 'Gateway Timeout',
                        505    => 'HTTP Version Not Supported'
                    );

    if ($code == '' OR ! is_numeric($code))
    {
        show_error('Status codes must be numeric', 500);
    }

    if (isset($stati[$code]) AND $text == '')
    {                
        $text = $stati[$code];
    }
    
    if ($text == '')
    {
        show_error('No status text available.  Please check your status code number or supply your own message text.', 500);
    }
    
    $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

    if (substr(php_sapi_name(), 0, 3) == 'cgi')
    {
        header("Status: {$code} {$text}", TRUE);
    }
    elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0')
    {
        header($server_protocol." {$code} {$text}", TRUE, $code);
    }
    else
    {
        header("HTTP/1.1 {$code} {$text}", TRUE, $code);
    }
}

// ------------------------------------------------------------------------

/**
* Check the string is a Obullo extension 
* which is defined in config/extensions.php
* 
* @param  string $name
* @return boolean
*/
function is_extension($name = '', $current_module = '')
{                         
    static $enabled_extensions = array();
    
    if($name == '') return FALSE;
    
    $extensions = get_config('extensions');
    
    if(is_array($extensions))
    {
        $defined_extensions = array_keys($extensions);

        if(count($defined_extensions) > 0) 
        {
            foreach($defined_extensions as $ext_key)
            {
                if(isset($enabled_extensions[$ext_key][$name]))
                {
                    return TRUE;
                }
                
                if(isset($extensions[$ext_key][$name]) AND is_dir(MODULES . $name))           
                {         
                    if($extensions[$ext_key][$name]['enabled'])   // Check extension is enabled.
                    {
                        if($ext_key == 'application') // If extension configured for application.
                        {
                            $enabled_extensions[$ext_key][$name] = $name;
                            return TRUE;
                        }
                        elseif($ext_key == $current_module)   // If extension configured for current module.
                        {
                            $enabled_extensions[$ext_key][$name] = $name;
                            return TRUE;
                        }
                        
                    }
                }                
            }
        }
    }
    
    return FALSE;
}

// ------------------------------------------------------------------------ 

/**
* Get current extension configuration.
* 
* @param   string $name
* @param   string $item
* @param   string $index
* @return  mixed | NULL
*/
function ext_item($name, $item, $index = 'application')
{
    $extensions = get_config('extensions');

    if(isset($extensions[$index][$name][$item]))
    {
        return $extensions[$index][$name][$item];
    }
    
    return NULL;
}


//----------------------------------------------------------------------- 
 
/**
* 404 Page Not Found Handler
*
* @access   private
* @param    string
* @return   string
*/
if( ! function_exists('show_404')) 
{
    function show_404($page = '')
    {   
        log_me('error', '404 Page Not Found --> '.$page);
        
        echo show_http_error('404 Page Not Found', $page, 'ob_404', 404);

        exit;
    }
}

// -------------------------------------------------------------------- 

/**
* Manually Set General Http Errors
* 
* @param string $message
* @param int    $status_code
* @param int    $heading
* 
* @version 0.1
* @version 0.2  added custom $heading params for users
*/
if( ! function_exists('show_error')) 
{
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
    {
        log_me('error', 'HTTP Error --> '.$message);
        
        // Some times we use utf8 chars in errors.
        header('Content-type: text/html; charset='.config_item('charset')); 
        
        echo show_http_error($heading, $message, 'ob_general', $status_code);
        
        exit;
    }
}
                   
// --------------------------------------------------------------------

/**
 * General Http Errors
 *
 * @access   private
 * @param    string    the heading
 * @param    string    the message
 * @param    string    the template name
 * @param    int       header status code
 * @return   string
 */
if( ! function_exists('show_http_error')) 
{
    function show_http_error($heading, $message, $template = 'ob_general', $status_code = 500)
    {
        set_status_header($status_code);

        $message = implode('<br />', ( ! is_array($message)) ? array($message) : $message);
        
        if(defined('CMD'))  // If Command Line Request
        {
            return '['.$heading.']: The url ' .$message. ' you requested was not found.'."\n";
        }
        
        ob_start();
        include(APP. 'core'. DS .'errors'. DS .$template. EXT);
        $buffer = ob_get_clean();
        
        return $buffer;
    }
}


// -------------------------------------------------------------------- 

/**
 * Check Array is_associative
 * or not.
 * 
 * @param type $a
 * @return type 
 */
if( ! function_exists('is_assoc_array')) 
{
    function is_assoc_array( $a )
    {
        return is_array( $a ) && ( count( $a ) !== array_reduce( array_keys( $a ), create_function( '$a, $b', 'return ($b === $a ? $a + 1 : 0);' ), 0 ) );
    }
}

// END Common.php File

/* End of file Common.php */
/* Location: ./obullo/core/Common.php */
