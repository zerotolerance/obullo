<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         obullo
 * @author          obullo.com
 * @copyright       Obullo Team
 * @filesource
 * @license
 */

Class ViewException extends CommonException {}

// ------------------------------------------------------------------------

/**
 * View Class
 *
 * Display static files.
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Libraries
 * @author        Obullo
 * @link
 */

Class OB_View {
    
    /**
    * Load view files.
    * 
    * @param string $path the view file path
    * @param string $filename view name
    * @param mixed  $data view data
    * @param booelan $string fetch the file as string or include file
    * @param booealan $return return false and don't show view file errors
    * @param string $func default view
    * @return void | string
    */
    public function view($path, $filename, $data = '', $string = FALSE, $return = FALSE, $func = 'view')
    {
        $_ob = load_class('Storage');
        
        foreach (get_object_vars(this()) as $_ob_key => $_ob_var) // This allows using "$this" variable in views files.
        {
            if ( ! isset($this->$_ob_key))
            {
                $this->{$_ob_key} =& this()->$_ob_key;
            }
        }
        
        _set_view_data($data);
        
	$data = $_ob->view->view_data;
        
        if ( ! file_exists($path . $filename . EXT) )
        {
            if($return)
            {
                log_me('debug', ucfirst($func).' file failed gracefully: '. $path . $filename . EXT);

                return;     // fail gracefully
            }

            throw new ViewException('Unable locate the '.$func.' file: '. $path . $filename . EXT);
        }
        
        if( empty($data) ) $data = array();

        loader::helper('ob/array');
        
        $data = object_to_array($data);

        if(sizeof($data) > 0) { extract($data, EXTR_SKIP); }

        ob_start();

        // If the PHP installation does not support short tags we'll
        // do a little string replacement, changing the short tags
        // to standard PHP echo statements.

        if ((bool) @ini_get('short_open_tag') === FALSE AND config_item('rewrite_short_tags') == TRUE)
        {
            echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($path.$filename. EXT))));
        }
        else
        {
            include($path . $filename . EXT);
        }

        log_me('debug', ucfirst($func).' file loaded: '.$path . $filename . EXT);

        if($string === TRUE)
        {
            $content = ob_get_contents();
            @ob_end_clean();

            return $content;
        }

        // Set Global views inside to Output Class for caching functionality..
        core_class('Output')->append_output(ob_get_contents());

        @ob_end_clean();

        return;
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Load view file private function.
    * 
    * @param type $file_url
    * @param type $folder
    * @param string $extra_path
    * @param type $base
    * @param type $custom
    * @return type 
    */
    function _load_file($file_url, $folder = 'views', $extra_path = '', $base = FALSE, $custom = FALSE)
    {
        $sub_module_path  = $GLOBALS['sub_path'];
        $application_view = FALSE;
        
        $file_url  = strtolower(trim($file_url, '/'));
        
        if(strpos($file_url, 'app/') === 0)   // application folder request
        {
           $file_url = substr($file_url, 4);
           $application_view = TRUE;
        }
        
        if(strpos($file_url, 'ob/') === 0)    // obullo folder request
        {
           $file_url = substr($file_url, 3);
           $base = TRUE;
        }
        
        if($base)  // if  /obullo/views
        {
            return array('filename' => $file_url, 'path' => BASE .$folder. DS);
        }
        
        if($custom) 
        {
            return array('filename' => $file_url, 'path' => BASE .$folder. DS);
        }
        
        $extension = FALSE;
     
        if(strpos($file_url, 'sub.') === 0)   // sub.module/module folder request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths          = explode('/', $file_url); 
            $filename       = array_pop($paths);       // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            $modulename     = array_shift($paths);     // get module name
            
            $sub_module_path = $sub_modulename. DS .'modules'. DS;
            
            /*
            if(is_extension($sub_modulename, $module))
            {
                $extension = TRUE; 
            }
             */
        }elseif(strpos($file_url, '../') === 0)  // if  ../modulename/file request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths      = explode('/', substr($file_url, 3));
            $filename   = array_pop($paths);          // get file name
            $modulename = array_shift($paths);        // get module name
            
            $module = (isset($GLOBALS['d'])) ? $GLOBALS['d'] : core_class('Router')->fetch_directory();
            
            if(is_extension($modulename, $module))
            {
                $extension = TRUE; 
            }
        }
        else    // if current modulename/file
        {
            $filename = $file_url;          
            $paths    = array();
            if( strpos($filename, '/') !== FALSE)
            {
                $paths      = explode('/', $filename);
                $filename   = array_pop($paths);
            }

            $modulename = $GLOBALS['d'];
        }

        $sub_path   = '';
        if( count($paths) > 0)
        {
            $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
        }

        if($extra_path != '')
        {
            $extra_path = str_replace('/', DS, trim($extra_path, '/')) . DS;
        }
        
        if($extension)
        {
            $extra_path = '';  // We don't need extra path for extensions
        }
        
        $module_path = MODULES .$sub_module_path.$modulename. DS .$folder. DS .$sub_path. $extra_path;
        $path        = $module_path;
        
        if(file_exists(APP .$folder. DS .$sub_path .$extra_path . $filename. EXT))
        {
            $path = APP .$folder. DS .$sub_path .$extra_path;
        }
        elseif(file_exists($module_path. $filename. EXT))  // first check module path
        {
            $path = $module_path;
        }
        
        if($application_view)
        {
            $path = APP .$folder. DS .$sub_path .$extra_path;
        }
    
        return array('filename' => $filename, 'path' => $path);
    }

    
}

// END View Class

/* End of file View.php */
/* Location: ./obullo/libraries/View.php */