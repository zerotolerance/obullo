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
* @return  string | NULL
*/
if ( ! function_exists('view_var'))
{
    function view_var($key, $val = '')
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
            return view_array($key, $val);
        }
        
        $view->view_var[$key][] = $val;

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
    function view_array($key, $val = array())
    {
        $view = lib('ob/View');
        $val  = (array)$val;
        
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
        
        return;
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
        $folder = 'views';
        if(strpos($file_url, 'layouts/') === 0) // include file and load view files form /layouts folder.
        {
            $string = FALSE;
        }
        elseif(strpos($file_url, '../layouts/') === 0)
        {
            $file_url = '../views/'.str_replace('../layouts/', '', $file_url);
            $folder   = 'layouts';
            $string   = FALSE;   
        }
        
        if(strpos($file_url, 'ob/') === 0)  // Obullo Core Views
        {
            $file_data = array('filename' => strtolower(substr($file_url, 3)), 'path' => BASE .'views'. DS);
        } 
        else
        {
            $file_data = loader::load_file($file_url, $folder, FALSE, FALSE);
        }
        
        return lib('ob/View')->load($file_data['path'], $file_data['filename'], $data, $string, FALSE);
    }
}

/* End of file view.php */
/* Location: ./obullo/helpers/view.php */