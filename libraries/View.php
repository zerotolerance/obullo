<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
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
    
    public $view_folder       = '';
    public $layout_folder     = '';
    public $layout_folder_msg = FALSE;
    public $css_folder        = '/';
    public $img_folder        = '/';

    public $view_var          = array(); // String type view variables
    public $view_array        = array(); // Array type view variables
    public $view_data         = array(); // Mixed type view variables
    public $layout_name       = '';
    
    /**
    * Constructor
    *
    * Sets the View variables and runs the compilation routine
    *
    * @version   0.1
    * @access    public
    * @return    void
    */
    public function __construct()
    {
        $this->view_folder      = DS .'';
        $this->layout_folder    = DS .'';
        
        log_me('debug', "View Class Initialized");
    }
    
    // ------------------------------------------------------------------------

    /**
    * View load function
    *
    * @access   private
    * @param    string   $path file path
    * @param    string   $filename
    * @param    array    $data template vars
    * @param    boolean  $string
    * @param    boolean  $return
    * @return   void
    */
    public function load($path, $filename, $data = '', $string = FALSE, $return = FALSE, $func = 'view')
    {
        return $this->view($path, $filename, $data, $string, $return, $func);
    }
    
    // ------------------------------------------------------------------------
    
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
        if(is_object(this()))
        {
            foreach(array_keys(get_object_vars(this())) as $key) // This allows to using "$this" variable in all views files.
            {
                if ( ! isset($this->$key))
                {
                    $this->{$key} = &this()->$key;
                }             
            }
        }
        
        //-----------------------------------
                
        $data = $this->_set_view_data($data); // Enables you to set data that is persistent in all views.

        //-----------------------------------
        
        if ( ! file_exists($path . $filename . EXT) )
        {
            if($return)
            {
                log_me('debug', ucfirst($func).' file failed gracefully: '. error_secure_path($path). $filename . EXT);

                return;     // fail gracefully
            }

            throw new ViewException('Unable locate the '.$func.' file: '. error_secure_path($path). $filename . EXT);
        }

        if(is_array($data) AND count($data) > 0) 
        { 
            extract($data, EXTR_SKIP); 
        }

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

        log_me('debug', ucfirst($func).' file loaded: '.error_secure_path($path). $filename . EXT);

        if($string === TRUE)
        {
            $output = ob_get_contents();
            @ob_end_clean();

            return $output;
        }
        
        // Render possible Exceptional errors.
        $output = ob_get_contents();
        
        // Set Layout views inside to Output Class for caching functionality.
        lib('ob/Output')->append_output($output);

        @ob_end_clean();

        return;
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Load view file private function.
    * 
    * @param string $file_url
    * @param string $folder
    * @param string $extra_path
    * @param bool $base
    * @param bool $custom
    * @return array 
    */
    public function _load_file($file_url, $folder = 'views', $extra_path = '', $base = FALSE)
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
        
        if(strpos($file_url, '../sub.') === 0)   // sub.module/module folder request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths          = explode('/', substr($file_url, 3)); 
            $filename       = array_pop($paths);       // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            $modulename     = array_shift($paths);     // get module name
            
            $sub_module_path = $sub_modulename. DS .SUB_MODULES;
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }

        }
        elseif(strpos($file_url, '../') === 0)  // if  ../modulename/file request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths      = explode('/', substr($file_url, 3));
            $filename   = array_pop($paths);          // get file name
            $modulename = array_shift($paths);        // get module name
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }

            //---------- Extension Support -----------//
            
            if(extension('enabled', $modulename) == 'yes') // If its a enabled extension
            {
                if(strpos(extension('path', $modulename), 'sub.') === 0) // If extension working path is a sub.module.
                {
                    $file_url = '../'.extension('path', $modulename).'/'.$modulename.'/'.$filename;

                    if($sub_path != '')
                    {
                        $file_url = '../'.extension('path', $modulename).'/'.$modulename.'/'.str_replace(DS, '/', $sub_path).'/'.$filename;
                    }
     
                    return $this->_load_file($file_url);
                }
            }
            
            //---------- Extension Support -----------//
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

            $modulename = (isset($GLOBALS['d'])) ? $GLOBALS['d'] : lib('ob/Router')->fetch_directory();
                        
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .modulename/folder/sub/file.php  sub dir support
            }
        }

        if($extra_path != '')
        {
            $extra_path = str_replace('/', DS, trim($extra_path, '/')) . DS;
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
    
    // ------------------------------------------------------------------------
    
    /**
    * Enables you to set data that is persistent in all views
    *
    * @author CJ Lazell
    * @param array $data
    * @access public
    * @return void
    */
    public function _set_view_data($data = '')
    {
        if($data == '') return;
        
        if(is_object($data)) // object to array.
        {
            return get_object_vars($data);
        }
        
        if(is_array($data) AND count($data) > 0 AND count($this->view_data) > 0)
        {
            $this->view_data = array_merge((array)$this->view_data, (array)$data);
        }
        else 
        {
            $this->view_data = $data;
        }
        
        return $this->view_data;
    }
    
}

// END View Class

/* End of file View.php */
/* Location: ./obullo/libraries/View.php */