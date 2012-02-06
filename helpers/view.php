<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
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
        $view = lib('ob/View');

        if($val == '')
        {
            if(isset($view->view_var[$key]))
            {
                $var = '';
                foreach($view->view_var[$key] as $value)
                {
                    $var .= $value;
                }

                return $var;
            }
        }

        if($val == array())
        {
            return view_array($key, $val, $use_layout, $layout_data);
        }
        
        $view->view_var[$key][] = $val;

        if($use_layout)  // include setted layout.
        {
            view_layout($view->layout_name, $layout_data);
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
        $val  = (array)$val;
        $view = lib('ob/View');

        if($val == array())
        {
            if(isset($view->view_array[$key]))
            {
                $var = array();
                foreach($view->view_array[$key] as $value)
                {
                    $var[] = $value;
                }

                return $var;
            }
        }

        foreach($val as $value)
        {
            $view->view_array[$key][] = $value;
        }

        if($use_layout)  // include setted layout.
        {
            view_layout($view->layout_name, $layout_data);
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
        lib('ob/View')->layout_name = $layout;
    }
}

// ------------------------------------------------------------------------

/**
* Create your custom folders and
* change all your view paths to supporting
* filexible subfolders.
*
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
        $view = lib('ob/View');

        switch ($func)
        {
            case 'view':
                $view->view_folder     = $folder;
                $view->view_folder_msg = $failure_msg;
                
                log_me('debug', "View() Function Paths Changed");
             break;

           case 'view_layout':
                $view->layout_folder     = $folder;
                $view->layout_folder_msg = $failure_msg;

                log_me('debug', "View_layout() Function Paths Changed");
             break;

           case 'css':
                $view->css_folder      = $folder;
                $view->css_folder_msg  = $failure_msg;

                log_me('debug', "Css() Function Paths Changed");
             break;

           case 'js':
                $view->js_folder       = $folder;
                $view->js_folder_msg   = $failure_msg;

                log_me('debug', "Js() Function Paths Changed");
             break;

           case 'img':
                $view->img_folder      = $folder;
                $view->img_folder_msg  = $failure_msg;

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
        $view = lib('ob/View');
        
        $return     = FALSE;
        $extra_path = '';
        
        if(isset($view->view_folder{1})) // if view folder changed don't show errors ..
        { 
            if($view->view_folder_msg) $return = TRUE;
            
            $extra_path = $view->view_folder;
        }    

        $file_info = $view->_load_file($file_url, 'views', $extra_path);
        
        profiler_set('views', $file_info['filename'], $file_info['path'] . $file_info['filename'] .EXT);

        return $view->load_view($file_info['path'], $file_info['filename'], $data, $string, $return, __FUNCTION__);
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
        $view = lib('ob/View');
        
        $return     = FALSE;
        $extra_path = '';
        
        if(isset($view->layout_folder{1}))  // if view_layout folder changed don't show errors ..
        { 
            if($view->layout_folder_msg) $return = TRUE; 
            
            $extra_path = $view->layout_folder;   
        }  
        
        $file_info = $view->_load_file($file_url, 'views', $extra_path);

        profiler_set('layouts', $file_info['filename'], $file_info['path'] . $file_info['filename'] .EXT);

        return $view->load_view($file_info['path'], $file_info['filename'], $data, $string, $return, __FUNCTION__);
    }
}


/* End of file view.php */
/* Location: ./obullo/helpers/view.php */