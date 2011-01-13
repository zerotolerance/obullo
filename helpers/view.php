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
 * @filesource
 * @license
 */


Class ViewException extends CommonException {}

/**
 * Obullo View Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Language
 * @author      Ersin Guvenc
 * @version     0.1
 * @version     0.2 added empty $data string support
 * @version     0.3 added set_view_folder function, added return , fail gracefully function for views.
 * @version     0.4 added img_folder to view_set_folder() function.
 * @version     0.5 renamed all function prefix as "view_".
 * @link
 */

if( ! isset($_ob->view))  // Helper Constructror
{
    $_ob = base_register('Empty');
    $_ob->view = new stdClass();

    $_ob->view->view_folder      = DS. '';
    $_ob->view->app_view_folder  = DS. '';
    $_ob->view->css_folder       = '/';
    $_ob->view->img_folder       = '/';

    $_ob->view->view_var         = array();
    $_ob->view->view_layout_name = '';

    log_me('debug', "View Helper Initialized");
}

// ------------------------------------------------------------------------

if ( ! function_exists('view_var'))
{
    function view_var($key, $val = '', $use_layout = FALSE, $layout_data = array())
    {
        $_ob = base_register('Empty');

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
            view_temp($_ob->view->view_layout_name, $layout_data);
        }

        return;
    }
}

if ( ! function_exists('view_array'))
{
    function view_array($key, $val = array(), $use_layout = FALSE, $layout_data = array())
    {
        $val= (array)$val;
        $_ob = base_register('Empty');

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
          $_ob->view->view_array[$key][] = $value;

        if($use_layout)  // include setted layout.
        {
            view_temp($_ob->view->view_layout_name, $layout_data);
        }

        return;
    }
}

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
        $_ob = base_register('Empty');
        $_ob->view->view_layout_name = $layout;
    }
}

/**
* Create your custom folders and
* change all your view paths to supporting
* multiple interfaces (iphone interface etc ..)
*
* @author   Ersin Guvenc
* @param    string $func view function
* @param    string $folder view folder (no trailing slash)
* @version  0.1
* @version  0.2 added img folder
*/
if ( ! function_exists('view_set_folder'))
{
    function view_set_folder($func = 'view', $folder = '')
    {
        $_ob = base_register('Empty');
        $folder_path = empty($folder) ? DS : $folder. DS;

        switch ($func)
        {
           case 'view':
             $_ob->view->view_folder     = DS. $folder_path;

             log_me('debug', "View() Function Paths Changed");
             break;

           case 'view_app':
             $_ob->view->app_view_folder = DS. $folder_path;

             log_me('debug', "View_temp() Function Paths Changed");
             break;

           case 'css':
             $_ob->view->css_folder      = $folder;

             log_me('debug', "Css() Function Paths Changed");
             break;

           case 'js':
             $_ob->view->js_folder      = $folder;

             log_me('debug', "Js() Function Paths Changed");
             break;

           case 'img':
             $_ob->view->img_folder      = $folder;

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
    function view($filename, $data = '', $string = TRUE)
    {
        $_ob = base_register('Empty');
        $return = FALSE;

        if(isset($_ob->view->view_folder{1})) { $return = TRUE; }    // if view folder changed don't show errors ..

        $path =  DIR .$GLOBALS['d']. DS .'views'. $_ob->view->view_folder;

        profiler_set('local_views', $filename, $path . $filename .EXT);

        return _load_view($path, $filename, $data, $string, $return, __FUNCTION__);
    }
}

// ------------------------------------------------------------------------

/**
* Load global view temp check if it exist
* in modules/views otherwise load it from
* application/layouts directory.
*
* @author CJ Lazell
* @param  string  $filename
* @param  array   $data
* @param  boolean $string
* @return void
*/
if ( ! function_exists('view_temp'))
{
    function view_temp($filename, $data = '', $string = FALSE)
    {
        $_ob = base_register('Empty');
        $return = FALSE;

        if(isset($_ob->view->app_view_folder{1})) { $return = TRUE; }  // if view folder changed don't show errors ..

        $path = APP .'layouts'. $_ob->view->app_view_folder;

        profiler_set('app_views', $filename, $path );

        return _load_view($path, $filename, $data, $string, $return, __FUNCTION__);
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
        $_ob = base_register('Empty');
		if(isset($_ob->view->view_data)) $_ob->view->view_data= array_merge((array)$_ob->view->view_data, (array)$data);
		else $_ob->view->view_data= $data;
  }
}

/**
* Render multiple view files.
*
* @param array $filenames
* @param array $data
*/
if ( ! function_exists('view_render'))
{
    function view_render($filenames = array(), $data = '')
    {
        $_ob = base_register('Empty');

        $var = '';
        foreach($filenames as $filename)
        {
            $var .= view($filename, $data, TRUE);
        }

        return $var;
    }
}

// ------------------------------------------------------------------------

/**
* Load Java script files externally
* like fetch view files as string
*
* @author   Ersin Guvenc
* @access   private
* @param    string  $path
* @param    string  $filename
* @param    array   $data
* @version  0.1
* @version  0.2 added empty $data
* @version  0.3 added short_open_tag support
* @param    array  $data
*/
if ( ! function_exists('_load_script'))
{
    function _load_script($path, $filename, $data = '')
    {
        if( empty($data) ) $data = array();

        if ( ! file_exists($path . $filename . EXT) )
        {
            throw new ViewException('Unable locate the script file: '. $path . $filename . EXT);
        }

        $data = _ob_object_to_array($data);

        if(sizeof($data) > 0) { extract($data, EXTR_SKIP); }

        ob_start();

        // Short open tag support.
        if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE)
        {
            echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($path.$filename.EXT))));
        }
        else
        {
            include($path . $filename . EXT);
        }

        $content = ob_get_contents();

        ob_end_clean();

        log_me('debug', 'Script file loaded: '.$path . $filename . EXT);

        profiler_set('scripts', $filename, $filename);

        return "\n".$content;
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
if ( ! function_exists('_load_view'))
{
    function _load_view($path, $filename, $data = '', $string = FALSE, $return = FALSE, $func = 'view')
    {
	    $_ob = base_register('Empty');
				_set_view_data($data);
				$data= $_ob->view->view_data;

				$module_extra= (strpos($filename, '../') !== 0)?'../'.$GLOBALS['d'].DS:'';
        $module_filename= substr($module_extra.$filename, 3);
				$module_path= DIR . preg_replace('/(\w+)\/(.+)/i', '$1/views/', $module_filename);
				$module_filename= preg_replace('/^(\w+\/)/', '', $module_filename);

				$is_module_file= file_exists($module_path . $module_filename . EXT);
				if($is_module_file)
				{
					$path     = $module_path;
					$filename = $module_filename;
				}
				else if ( ! file_exists($path . $filename . EXT) )
        {
            if($return)
            {
                log_me('debug', 'View file failed gracefully: '. $path . $filename . EXT);

                return;     // fail gracefully for different interfaces ..
                            // iphone, blackberry etc..
            }

            throw new ViewException('Unable locate the view file: '. $filename . EXT);
        }
				
        if( empty($data) ) $data = array();


        $data = _ob_object_to_array($data);

        if(sizeof($data) > 0) { extract($data, EXTR_SKIP); }

        ob_start();

        // If the PHP installation does not support short tags we'll
        // do a little string replacement, changing the short tags
        // to standard PHP echo statements.

        if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE)
        {
            echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($path.$filename.EXT))));
        }
        else
        {
            include($path . $filename . EXT);
        }

        log_me('debug', 'View file loaded: '.$path . $filename . EXT);

        if($string === TRUE)
        {
            $content = ob_get_contents();
            @ob_end_clean();

            return $content;
        }

        // Set Global views inside to Output Class for caching functionality..
        base_register('Output')->append_output(ob_get_contents());

        @ob_end_clean();

        return;

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
if ( ! function_exists('_ob_object_to_array'))
{
    function _ob_object_to_array($object)
    {
        return (is_object($object)) ? get_object_vars($object) : $object;
    }
}

/* End of file view.php */
/* Location: ./obullo/helpers/view.php */




