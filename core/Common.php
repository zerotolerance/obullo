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
* A Php5 library must be contain that functions.
*/
interface PHP5_Library
{
    public static function instance();
}

/**
* base_register()
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
function base_register($realname, $new_object = NULL, $params_or_no_ins = '')
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

    if(file_exists(BASE .'libraries'. DS . $Class. EXT))
    {
        if( ! isset($new_objects[$Class]) )  // check new object instance
        {
            require(BASE .'libraries'. DS . $Class. EXT);
        }
        
        $classname = $Class;    // prepare classname

        if($params_or_no_ins === FALSE)
        {
            profiler_set('libraries', 'php_'.$Class.'_no_instantiate', $Class);
            return TRUE;
        }

        $classname = 'OB_'.$Class;
        $prefix    = config_item('subclass_prefix');  // MY_

        if(file_exists(APP .'libraries'. DS .$prefix. $Class. EXT))
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
function register_autoload($real_name)
{
    if(class_exists($real_name))
    return;

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

            // If local Global Controller file exist ..
            if(file_exists(DIR .$GLOBALS['d']. DS .'parents'. DS .$real_name. EXT))
            {            
                require(DIR .$GLOBALS['d']. DS .'parents'. DS .$real_name. EXT);

                profiler_set('parents', $real_name, DIR .$GLOBALS['d']. DS .'parents'. DS .$real_name. EXT);

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

        // Strlower class name.
        // --------------------------------------------------------------------
        $class  = strtolower($real_name); // lowercase classname.
        $prefix = config_item('subclass_prefix');

        // Local php5 libraries load support.
        // --------------------------------------------------------------------
        if(file_exists(DIR .$GLOBALS['d']. DS .'libraries'. DS .'php5'. DS .$class. EXT))
        {
            require(DIR .$GLOBALS['d']. DS .'libraries'. DS .'php5'. DS .$class. EXT);

            profiler_set('libraries', 'php5_local_'.$class.'_loaded', $class);
            return;
        }

        // Php5 application library load and replace support.
        // --------------------------------------------------------------------
        if(file_exists(APP .'libraries'. DS .'php5'. DS .$class. EXT))
        {
            $replaced = '_loaded';
            require(APP .'libraries'. DS .'php5'. DS .$class. EXT);

            if(file_exists(BASE .'libraries'. DS .'php5'. DS .ucfirst($class). EXT))
            $replaced = '_replaced';

            profiler_set('libraries', 'php5_'.$class. $replaced, $class);
            return;
        }

        // Php5 library extend (override) support.
        // --------------------------------------------------------------------
        if(file_exists(APP .'libraries'. DS .'php5'. DS . $prefix .$class. EXT))
        {
            require(APP .'libraries'. DS .'php5'. DS . $prefix .$class. EXT);

            profiler_set('libraries', 'php5_'.$class.'_overridden', $prefix. $class);
            return;
        }

        // load classname_CORE libraries.
        // --------------------------------------------------------------------
        if(substr($class, -5) == '_core')
        {
            $name = explode('_', $class);

            require(BASE .'libraries'. DS .'php5'. DS .ucfirst($name[0]). EXT);

            profiler_set('libraries', 'php5_'.$class, $class);
            return;
        }

        // else call directly php5 base libraries.
        // --------------------------------------------------------------------
        if(file_exists(BASE .'libraries'. DS .'php5'. DS .ucfirst($class). EXT))
        {
            require(BASE .'libraries'. DS .'php5'. DS .ucfirst($class). EXT);

            eval('Class '.$class.' extends '.$class.'_CORE {}');

            profiler_set('libraries', 'php5_'.$class.'_loaded', $class);
            return;
        }

        return;
}

spl_autoload_register('register_autoload',true);

// --------------------------------------------------------------------

/**
* Load system helpers
*
* @access   private
* @param    mixed $filename
* @param    mixed $folder
*/
function loaded_helper($helper)
{
    if(file_exists(BASE .'helpers'. DS .'loaded'. DS .$helper. EXT))
    {
        $prefix = config_item('subhelper_prefix');

        // If user helper file exist ..
        if(file_exists(APP .'helpers'. DS .$prefix. $helper. EXT))
        {
            include(APP .'helpers'. DS .$prefix. $helper. EXT);
            profiler_set('loaded_helpers', $prefix . $helper, $prefix . $helper);
        }

        include(BASE .'helpers'. DS .'loaded'. DS .$helper. EXT);
        profiler_set('loaded_helpers', $helper, $helper);
        return;
    }

    throw new LoaderException('Unable to locate the base helper: ' .$helper. EXT);
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

    if ( ! isset($static[$filename]))
    {
        if ( ! file_exists($folder. DS .$filename. EXT))
        throw new CommonException('The static file '. DS .$folder. DS .$filename. EXT .' does not exist.');

        require($folder. DS .$filename. EXT);

        if($var == '') $var = &$filename;

        if ( ! isset($$var) OR ! is_array($$var))
        {
            throw new CommonException('The static file '. DS .$folder. DS .$filename. EXT .' file does
            not appear to be formatted correctly.');
        }

        $static[$filename] =& $$var;
     }

    return $static[$filename];
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
        return FALSE;

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
        return FALSE;

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
    return;

    log_write($level, $message, $php_error);
}


/**
* Codeigniter Backward Compatibility.
* Please use log_me() function instead of log_message();
*
* @access public
*/
function log_message($level = 'error', $message, $php_error = FALSE)
{
    return log_me($level, $message, $php_error);
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

/**
* Set data to profiler variable
*
* @param    string $type log type
* @param    string $key  log key
* @param    string $val  log val
*/
function profiler_set($type, $key, $val)
{
    base_register('Empty')->profiler_var[$type][$key] = $val;
}

/**
* Get profiler data from profiler
* variable.
*
* @param    string $type log type
* @return   array
*/
function profiler_get($type)
{
    $empty = base_register('Empty');
    
    if( isset($empty->profiler_var[$type]))
    {
        return $empty->profiler_var[$type];
    };

    return array();
}

// ------------------------------------------------------------------------

/**
* Parse head files to learn whether it
* comes from modules directory.
*
* convert  this path ../welcome/welcome.css
* to /public_url/modules/welcome/public/css/welcome.css
*
* @author   CJ Lazell
* @author   Ersin Guvenc
* @access   private
* @param    mixed $file_url
* @param    mixed $extra_path
* @return   string | FALSE
*/
if( ! function_exists('_get_public_path') )
{
    function _get_public_path($file_url, $extra_path = '')
    {
        $ob = this();

        $extra_path = ( $extra_path == '') ? '' : $extra_path . '/';

        if( strpos($file_url, '../') === 0)  // module request
        {
            $paths      = explode('/', substr($file_url, 3));
            $filename   = array_pop($paths);                   // get file name
            $modulename = array_shift($paths);                 // get module name

            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path   = implode('/', $paths) . '/';      // .public/css/sub/welcome.css  sub dir support
            }

            $extension  = substr(strrchr($filename, '.'), 1);  // get extension
            if($extra_path != '') $folder     = $extension;

            if($extension == FALSE)
            {
                return FALSE;
            }

            $full_path     = $ob->config->public_url('', true) .str_replace(DS, '/', trim(DIR, DS)). '/';
            $public_folder = $ob->config->item('public_folder');

            if ( strpos($ob->config->item('public_url'), '://') !== FALSE)  // if user want to keep static files in another server.
            {                                                               // don't touch the current configuration.
                $public_folder = $ob->config->item('public_folder');

                // example
                // http://static.myserver.com/site/modules/welcome/public/css/welcome.css
                // http://static.myserver.com/admin/modules/welcome/public/css/welcome.css
            }
            else
            {
                if( strpos($public_folder, '/') !== FALSE)     // if config public_folder = 'public/site' just grab the 'public' word
                {                                              // so in multi applications user don't need to divide public folder files.
                    $public_folder = current(explode('/', $public_folder));
                }

                // example
                // http://example.com/site/modules/welcome/public/css/welcome.css    (public/{site removed}/css/welcome.css)
                // http://example.com/site/modules/welcome/public/css/welcome.css
            }

            if(!$extra_path) $full_path .= $modulename . '/'. $public_folder .'/' . $extension .'/'. $sub_path . $filename;
            else '/'. $full_path .= $modulename . '/'. $public_folder .'/' . $extra_path . $sub_path . $filename;

        }
        else
        {
            $filename = $file_url;
            $sub_path = '';

            if( strpos($file_url, '/') !== FALSE)
            {
                $paths    = explode('/', $file_url);
                $filename = array_pop($paths);
                $sub_path = implode('/', $paths) . '/';   // .public/css/sub/welcome.css  sub support
            }

            if(!$extra_path) $folder = substr(strrchr($filename, '.'), 1) . '/';  // get extension
            else $folder= '';

            if($folder === FALSE)
            {
                return FALSE;
            }

            $full_path = $ob->config->public_url(). $extra_path . $folder . $sub_path . $filename;

            //echo $full_path;
        }

        return $full_path;
    }

}

// END Common.php File

/* End of file Common.php */
/* Location: ./obullo/core/Common.php */
