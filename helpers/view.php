<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         obullo
 * @author          obullo.com
 * @filesource
 * @license
 */

/**
 * Obullo View Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Language
 * @link
 */

if( ! isset($_ob->view))  // Helper Constructror
{
    $_ob = load_class('Storage');
    $_ob->view = new stdClass();

    $_ob->view->view_folder      = DS .'';
    $_ob->view->layout_folder    = DS .'';
    $_ob->view->css_folder       = '/';
    $_ob->view->img_folder       = '/';
    $_ob->view->script_folder    = DS .'';

    $_ob->view->view_var         = array();
    $_ob->view->layout_name      = '';

    log_me('debug', "View Helper Initialized");
}

// ------------------------------------------------------------------------

/**
* Create view variables for layouts
* 
* @param  string $key
* @param  string $val
* @param  boolean $use_layout
* @param  array $layout_data
* @return  string | NULL
*/
if ( ! function_exists('view_var'))
{
    function view_var($key, $val = '', $use_layout = FALSE, $layout_data = array())
    {
        $_ob = load_class('Storage');

        if($val == '')
        {
            if(isset($_ob->view->view_var[$key]))
            {
                $var = '';
                foreach($_ob->view->view_var[$key] as $value)
                {
                    $var .= $value;
                }

                return $var;
            }
        }

        $_ob->view->view_var[$key][] = $val;

        if($use_layout)  // include setted layout.
        {
            view_layout($_ob->view->layout_name, $layout_data);
        }

        return;
    }
}

// ------------------------------------------------------------------------

/**
* Create view arrays for layouts
* 
* @param  string $key
* @param  array $val
* @param  boolean $use_layout
* @param  array $layout_data
* @return  string | NULL
*/
if ( ! function_exists('view_array'))
{
    function view_array($key, $val = array(), $use_layout = FALSE, $layout_data = array())
    {
        $val= (array)$val;
        $_ob = load_class('Storage');

        if($val == array())
        {
            if(isset($_ob->view->view_array[$key]))
            {
                $var = array();
                foreach($_ob->view->view_array[$key] as $value)
                {
                    $var[] = $value;
                }

                return $var;
            }
        }

        foreach($val as $value)
        {
            $_ob->view->view_array[$key][] = $value;
        }

        if($use_layout)  // include setted layout.
        {
            view_layout($_ob->view->layout_name, $layout_data);
        }

        return;
    }
}

// ------------------------------------------------------------------------

/**
* Set layout for all controller
* functions.
*
* @param string $layout
*/
if ( ! function_exists('view_set'))
{
    function view_set($layout)
    {
        $_ob = load_class('Storage');
        
        $_ob->view->layout_name = $layout;
    }
}

// ------------------------------------------------------------------------

/**
* Create your custom folders and
* change all your view paths to supporting
* multiple interfaces (iphone interface etc ..)
*
* @author   Ersin Guvenc
* @param    string $func view function
* @param    string $folder view folder (no trailing slash)
* @param    string $failure_msg
* @version  0.1
* @version  0.2 added img folder
*/
if ( ! function_exists('view_set_folder'))
{
    function view_set_folder($func = 'view', $folder = '', $failure_msg = FALSE)
    {
        $_ob = load_class('Storage');

        switch ($func)
        {
            case 'view':
                $_ob->view->view_folder     = $folder;
                $_ob->view->view_folder_msg = $failure_msg;
                
                log_me('debug', "View() Function Paths Changed");
             break;

           case 'view_layout':
                $_ob->view->layout_folder     = $folder;
                $_ob->view->layout_folder_msg = $failure_msg;

                log_me('debug', "View_layout() Function Paths Changed");
             break;

           case 'css':
                $_ob->view->css_folder      = $folder;
                $_ob->view->css_folder_msg  = $failure_msg;

                log_me('debug', "Css() Function Paths Changed");
             break;

           case 'js':
                $_ob->view->js_folder       = $folder;
                $_ob->view->js_folder_msg   = $failure_msg;

                log_me('debug', "Js() Function Paths Changed");
             break;

           case 'img':
                $_ob->view->img_folder      = $folder;
                $_ob->view->img_folder_msg  = $failure_msg;

                log_me('debug', "Img() Function Paths Changed");
               break;
        }
        
        return TRUE;
    }
}

// ------------------------------------------------------------------------

/**
* Load local view file
*
* @param string  $filename
* @param array   $data
* @param boolean $string default TRUE
* @return void
*/
if ( ! function_exists('view'))
{
    function view($file_url, $data = '', $string = TRUE)
    {
        $_ob = load_class('Storage');
        
        $return     = FALSE;
        $extra_path = '';
        
        if(isset($_ob->view->view_folder{1})) // if view folder changed don't show errors ..
        { 
            if($_ob->view->view_folder_msg) $return = TRUE;
            
            $extra_path = $_ob->view->view_folder;
        }    

        $file_info = lib('ob/view')->_load_file($file_url, 'views', $extra_path);
        
        profiler_set('views', $file_info['filename'], $file_info['path'] . $file_info['filename'] .EXT);

        return load_view($file_info['path'], $file_info['filename'], $data, $string, $return, __FUNCTION__);
    }
}

// ------------------------------------------------------------------------

/**
* Load layouts file check if it exist
* in modules/layouts otherwise load it from
* application/layouts directory.
*
* @author CJ Lazell
* @param  string  $filename
* @param  array   $data
* @param  boolean $string
* @return void
*/
if ( ! function_exists('view_layout'))
{
    function view_layout($file_url, $data = '', $string = FALSE)
    {
        $_ob = load_class('Storage');
        
        $return     = FALSE;
        $extra_path = '';
        
        if(isset($_ob->view->layout_folder{1}))  // if view_layout folder changed don't show errors ..
        { 
            if($_ob->view->layout_folder_msg) $return = TRUE; 
            
            $extra_path = $_ob->view->layout_folder;   
        }  
        
        $file_info = lib('ob/view')->_load_file($file_url, 'views', $extra_path);

        profiler_set('layouts', $file_info['filename'], $file_info['path'] . $file_info['filename'] .EXT);

        return load_view($file_info['path'], $file_info['filename'], $data, $string, $return, __FUNCTION__);
    }
}

// ------------------------------------------------------------------------

/**
* _set_view_data
*
* Enables you to set data that is persistent in all views
*
* @author CJ Lazell
* @param array $data
* @access public
* @return void
*/

if ( ! function_exists('_set_view_data'))
{
  function _set_view_data($data = array())
  {
        $_ob = load_class('Storage');
        
        if(isset($_ob->view->view_data)) 
        {
            $_ob->view->view_data = array_merge((array)$_ob->view->view_data, (array)$data);
        }
        else 
        {
            $_ob->view->view_data = $data;
        }
  }
}

// ------------------------------------------------------------------------

/**
* Main view function
*
* @author   Ersin Guvenc
* @access   private
* @param    string   $path file path
* @param    string   $filename
* @param    array    $data template vars
* @param    boolean  $string
* @param    boolean  $return
* @version  0.1
* @version  0.2 added empty $data
* @version  0.3 added $return param
* @version  0.4 added log_me()
* @version  0.4 added added short_open_tag support
* @return   void
*/
if ( ! function_exists('load_view'))
{
    function load_view($path, $filename, $data = '', $string = FALSE, $return = FALSE, $func = 'view')
    {
        return lib('ob/view')->view($path, $filename, $data, $string, $return, $func);
    }
}

// ------------------------------------------------------------------------

/**
* Object to Array
*
* Takes an object as input and converts the class variables to array key/vals
*
* @access   private
* @param    object
* @return   array
*/
if ( ! function_exists('view_object_to_array'))
{
    function view_object_to_array($object)
    {
        return (is_object($object)) ? get_object_vars($object) : $object;
    }
}

/* End of file view.php */
/* Location: ./obullo/helpers/view.php */