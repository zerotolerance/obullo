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
    public function load($path, $filename, $data = '', $string = FALSE, $return = FALSE, $func = 'view')
    {
        if(is_object(this()))
        {
            foreach(array_keys(get_object_vars(this())) as $key) // This allows to using "$this" variable in all views files.
            {
                // Don't do lazy loading => isset() in here object variables always
                // must be ## NEW ##. 
                // e.g. loader::config('somefile_vars'); $this->config->item('somefile_vars')
                
                $this->{$key} = &this()->$key;          
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

            throw new Exception('Unable locate the '.$func.' file: '. error_secure_path($path). $filename . EXT);
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
    * Enables you to set data that is persistent in all views
    *
    * @author CJ Lazell
    * @param array $data
    * @access public
    * @return void
    */
    public function _set_view_data($data = '')
    {
        if($data == '')
        {
            return;
        }
        
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