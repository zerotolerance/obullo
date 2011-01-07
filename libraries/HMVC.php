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

Class HMVCException extends CommonException {} 

 /**
 * HMVC Class
 * Hierarcial Model View Controller
 * Parses URIs and determines routing
 *
 * @package     Obullo
 * @subpackage  Libraries
 * @category    HMVC Request Class
 * @author      Ersin Guvenc
 * @version     0.1
 * @version     0.2  fixed this() bug, copied all this() object and assigned
 *              to instance using Controller::set_instance(); method.
 *              Hmvc router and uri libraries merged.
 */
Class OB_HMVC
{ 
    public $uri_string   = '';
    public $query_string = '';
    public $clone_this   = NULL;
    
    public $uri;     // Clone original URI object
    public $router;  // Clone original Router object
    
    public $response         = '';
    public $request_method   = 'GET';
    public $hmvc_connect     = TRUE;
    public $cache_time       = '';
    
    // Post and Get variables
    public $request_keys     = array();
    public $_GET_BACKUP      = '';
    public $_POST_BACKUP     = '';
    public $_REQUEST_BACKUP  = ''; 
    
    public function __construct()
    {
        log_me('debug', "HMVC Class Initialized");
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Call HMVC Request (Set the URI String).
    *
    * @access    private
    * @param     $hvmc  boolean
    * @param     $cache_time integer
    * @return    void                           
    */    
    public function hmvc_request($hmvc_uri = '', $cache_time = '')
    {
        // We need create backup of main controller $this object
        // becuse of it will change foreach HMVC requests.

        $this->clone_this = this();
        
        if($hmvc_uri != '')
        {          
            $URI    = base_register('URI');
            $Router = base_register('Router');
            
            $this->uri    = clone $URI;     // Create copy of original URI class.
            $this->router = clone $Router;  // Create copy of original Router class.
            
            $URI->clear();           // Reset uri objects we will use it for hmvc.
            $Router->clear();        // Reset router objects we will use it for hmvc.
            
            $this->cache_time = $cache_time;
            $this->uri_string = $URI->_filter_uri($hmvc_uri);   // secure URLS
            
            if(strpos($this->uri_string, '?') > 0)
            {
                $uri_part = explode('?', $this->uri_string);
                $URI->set_uri_string($uri_part[0]);
                
                $this->query_string = $uri_part[0] .'?'. $uri_part[1];
            }
            
            $Router->_set_routing();
            
            return;
        }
        
        $this->uri_string = '';     
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Reset all variables for multiple
    * HMVC requests.
    * 
    * @return   void
    */
    public function clear()
    {
        $this->keyval       = array();
        $this->uri_string   = '';
        $this->query_string = '';
        $this->cache_time   = '';        

        $this->reponse      = '';
        $this->clone_this   = '';
        $this->request_keys = array();
        $this->hmvc_connect = TRUE;
        
        $this->uri          = '';
        $this->router       = '';
        
        $this->request_method   = 'GET';
        $this->_GET_BACKUP      = '';
        $this->_POST_BACKUP     = '';
        $this->_REQUEST_BACKUP  = '';   
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set HMVC Request Method
    * 
    * @param    string $method
    * @param    array $params
    */
    public function set_method($method = 'GET' , $params = array())
    {
        $this->request_method = $method;
        
        if($this->query_string != '')
        {
            $query_str_params = $this->parse_query($this->query_string);
            
            if(count($query_str_params) > 0) 
            {
                $params = array_merge($query_str_params, $params);
            }
        }
        
        $this->_GET_BACKUP     = $_GET;         // Overload to $_REQUEST variables ..
        $this->_POST_BACKUP    = $_POST;
        $this->_REQUEST_BACKUP = $_REQUEST;
        
        $_POST = $_GET = $_REQUEST = array();   // reset global variables
        
        switch ($method) 
        {
           case 'POST':
            foreach($params as $key => $val)
            {
                $_POST[$key]    = $val;
                $_REQUEST[$key] = $val;
                
                $this->request_keys[$key] = '';
            }
             break;
             
           case 'GET':
           foreach($params as $key => $val)
            {
                $_GET[$key]     = $val;
                $_REQUEST[$key] = $val;
                
                $this->request_keys[$key] = '';
            }
             break;
        }   
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Parse Url
    * 
    * @param array $arr
    */
    public function parse_query($query_string = '')
    {
        if($query_string == '') return array(); 
        
        $separator = ini_get('arg_separator.output');
        if ($separator == '&amp;') 
        {
            $separator = '&';
        }

        $query_string  = parse_url($query_string, PHP_URL_QUERY);
        $query_string  = html_entity_decode($query_string);
        $query_string  = explode($separator, $query_string);
        $arr  = array();
        
        foreach($query_string as $val)
        {
            $words = explode('=', $val);
             
             if(isset($words[0]) AND isset($words[1]))
             {
                 $arr[$words[0]] = $words[1];
             }
        }
        
        unset($val, $words, $query_string);
        
        return $arr;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Execute Hmvc Request
    * 
    * @return   string
    */
    public function exec()
    {
        /*
        if($this->hmvc_connect === FALSE) 
        {
            return FALSE;
        } 
        */    
        $URI    = base_register('URI');
        $router = base_register('Router');
        $config = base_register('Config');
        $output = base_register('Output');

        $GLOBALS['d']   = $router->fetch_directory();   // Get requested directory
        $GLOBALS['s']   = $router->fetch_subfolder();   // Get requested subfolder
        $GLOBALS['c']   = $router->fetch_class();       // Get requested controller
        $GLOBALS['m']   = $router->fetch_method();      // Get requested method
    
        // a Hmvc uri must be unique otherwise may collission with standart uri.
        $URI->uri_string = '__HMVC_URI__'. $URI->uri_string;
        $URI->cache_time = $this->cache_time ;
        
        $display_cache = $output->_display_cache($config, $URI, TRUE);
        
        if($display_cache != '' AND $display_cache !== FALSE) // Check request uri if there is a HMVC cached file exist.
        {
            $this->_set_response($display_cache);
            
            $this->_reset_router();
            
            return TRUE;
        }
        
        if($GLOBALS['s'] != '')  // sub folder request ?
        {
            $hmvc_uri = "{$GLOBALS['d']} / {$GLOBALS['s']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            // Check the sub controller exists or not
            if ( ! file_exists(DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT))
            {   
                $this->_set_response('Hmvc request not found: '.$hmvc_uri);

                $this->_reset_router();
                
                return FALSE;
            }
            
            $controller = DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT;   
            $arg_slice  = 4;
            
            // Call the requested method.                1        2       3       4
            // Any URI segments present (besides the directory/subfolder/class/method) 
        } 
        else
        {
            $hmvc_uri = "{$GLOBALS['d']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            // Check the controller exists or not
            if ( ! file_exists(DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['c']. EXT))
            {   
                $this->_set_response('HMVC Unable to load your controller.Check your routes in Routes.php file is valid.');

                $this->_reset_router();
                
                return FALSE;
            }
            
            $controller = DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['c']. EXT;
            $arg_slice  = 3;
        }
        
        // Call the controller.
        require_once($controller);
        
        if ( ! class_exists($GLOBALS['c']) OR $GLOBALS['m'] == 'controller' 
              OR $GLOBALS['m'] == '_output'       
              OR $GLOBALS['m'] == '_hmvc_output'
              OR $GLOBALS['m'] == 'instance'
              OR in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods('Controller')))
            )
        {
            $this->_set_response('Hmvc request not found: '.$hmvc_uri);

            $this->_reset_router();
            
            return FALSE;
        }
        
        // If Everyting ok Declare Called Controller !
        $OB = new $GLOBALS['c']();

        // Check method exist or not
        if ( ! in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods($OB))))
        {
            $this->_set_response('Hmvc request not found: '.$hmvc_uri);

            $this->_reset_router();
            
            return FALSE;
        }
    
        ob_start();
        
        // Call the requested method.                1       2       3
        // Any URI segments present (besides the directory/class/method) 
        // will be passed to the method for convenience
        call_user_func_array(array($OB, $GLOBALS['m']), array_slice($URI->rsegments, $arg_slice)); 
         
        $content = ob_get_contents();
        
        // Write cache file if cache on ! and Send the final rendered output to the browser
        $output->_display_hmvc($content, $URI);
        
        @ob_end_clean();
        
        $this->_set_response($content);
            
        //---------------------- Reset Variables ------------------//
        $this->_reset_router();
            
        return TRUE;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Reset router for mutiple internal
    * hmvc requests.
    * 
    * @return   void
    */
    private function _reset_router()
    {
        while (@ob_end_clean());  // close all buffers
        
        $_POST = $_GET = $_REQUEST = array();
        $_GET     = $this->_GET_BACKUP;           // Assign global variables we copied before ..
        $_POST    = $this->_POST_BACKUP;
        $_REQUEST = $this->_REQUEST_BACKUP;
        
        // Set original objects foreach HMVC requests we backup before  ..
        
        $this->clone_this->uri    = base_register('URI', $this->uri);
        $this->clone_this->router = base_register('Router', $this->router);
        
        this($this->clone_this);    // set instance to original $this that we backup before
                         
        $GLOBALS['d']   = $this->router->fetch_directory();   // Assign Original Router methods we copied before
        $GLOBALS['s']   = $this->router->fetch_subfolder();   
        $GLOBALS['c']   = $this->router->fetch_class();       
        $GLOBALS['m']   = $this->router->fetch_method();
        
        $this->clear();  // reset all variables.
                         // reset $GLOBALS
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set hmvc response.
    * 
    * @param    mixed $data
    * @return   void
    */
    private function _set_response($data = '')
    {
        $this->response = $data;
    }    
    
    // --------------------------------------------------------------------
    
    /**
    * Get final hmvc response.
    * 
    * @return  string
    */
    public function response()
    {
        return $this->response;
    }


}
// END HMVC Class

/* End of file HMVC.php */
/* Location: ./obullo/libraries/HMVC.php */