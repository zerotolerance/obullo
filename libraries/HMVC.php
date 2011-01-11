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

function ob_request_timer($mark = '')
{
    list($sm, $ss) = explode(' ', microtime());

    return ($sm + $ss);
}

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
    public $uri_string    = ''; 
    public $query_string  = '';
    public $benchmark     = '';    // stores benchmark info
    public $request_times = '';    // request time for profiler
    public $start_time    = '';    // benchmark start time for profiler
    public $request_count = 0;     // request count for profiler
    
    // Cloned objects
    public $uri;                   // Clone original URI object
    public $router;                // Clone original Router object
    public $config;                // Clone original Config object
    public $_this         = NULL;   // Clone original this(); ( Controller instance)
    
    public $response         = '';
    public $request_keys     = array();
    public $request_method   = 'GET';
    public $hmvc_connect     = TRUE;
    public $no_loop          = FALSE;
    public $cache_time       = '';
    
    // Global variables
    public $_GET_BACKUP      = '';
    public $_POST_BACKUP     = '';
    public $_REQUEST_BACKUP  = ''; 
    public $_SERVER_BACKUP   = ''; 
    public $_PUT             = ''; 
    
    // Cache and Connection
    private $_conn_string    = '';       // Unique HMVC connection string that we need to convert it to conn_id.
    private static $_conn_id = array();  // Static HMVC Connection ids.
    
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
        $this->_set_conn_string($hmvc_uri);
        
        $this->_this = clone this();       // We need create backup $this object of main controller 
                                     // becuse of it will change foreach HMVC requests.
        if($hmvc_uri != '')
        {          
            $URI    = base_register('URI');
            $Router = base_register('Router');
            
            $this->uri    = clone $URI;     // Create copy of original URI class.
            $this->router = clone $Router;  // Create copy of original Router class.
            $this->config = clone base_register('Config');  // Create copy of original Config class.
            
            $URI->clear();           // Reset uri objects we will use it for hmvc.
            $Router->clear();        // Reset router objects we will use it for hmvc.
            
            $Router->hmvc = TRUE;    // We need to know Router class whether to use HMVC.
            
            $this->cache_time = $cache_time;
            $this->uri_string = $URI->_filter_uri($hmvc_uri);   // secure URLS
            
            if(strpos($this->uri_string, '?') > 0)
            {
                $uri_part = explode('?', $this->uri_string);
                $this->query_string = $uri_part[0] .'?'. $uri_part[1];
                
                $URI->set_uri_string($uri_part[0]);
            }
            else 
            {
                $URI->set_uri_string($this->uri_string);
            }
            
            $this->hmvc_connect = $Router->_set_routing();
            
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
        $this->_conn_string = '';
        $this->uri_string   = '';
        $this->query_string = '';
        $this->cache_time   = '';       

        $this->reponse      = '';
        $this->request_keys = array();
        $this->hmvc_connect = TRUE;
        $this->no_loop      = FALSE;
        
        // Clone objects
        $this->uri          = '';
        $this->router       = '';
        $this->config       = '';
        $this->_this        = '';
    
        $this->request_method   = 'GET';
    
        // Global variables
        $this->_GET_BACKUP      = '';
        $this->_POST_BACKUP     = '';
        $this->_REQUEST_BACKUP  = '';   
        $this->_SERVER_BACKUP   = '';   
        $this->_PUT             = '';   
    }
    
    // --------------------------------------------------------------------
    
    public function no_loop()
    {
        $this->no_loop = TRUE;
        
        return $this;
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
        $method = strtoupper($method);
        
        $this->_set_conn_string($method);
        $this->_set_conn_string(serialize($params));
        
        $this->request_method = $method;
        
        if($this->query_string != '')
        {
            $query_str_params = $this->parse_query($this->query_string);
            
            if(count($query_str_params) > 0 AND ($method == 'GET' || $method == 'DELETE')) 
            {
                $params = array_merge($query_str_params, $params);
            }
        }
        $this->_GET_BACKUP     = $_GET;         // Overload to $_REQUEST variables ..
        $this->_POST_BACKUP    = $_POST;
        $this->_SERVER_BACKUP  = $_SERVER;
        $this->_REQUEST_BACKUP = $_REQUEST;
        
        $_SERVER = $_POST = $_GET = $_REQUEST = array();   // reset global variables
        
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
             
           case ($method == 'GET' || $method == 'DELETE'):
            foreach($params as $key => $val)
            {
                $_GET[$key]     = $val;
                $_REQUEST[$key] = $val;
                
                $this->request_keys[$key] = '';
            }
             break;
             
           case 'PUT':
            // Methods besides GET and POST do not properly parse the form-encoded
            // query string into the $_POST array, so we overload it manually.
            // parse_str(file_get_contents('php://input'), $_POST);
             break;
        }
    
        $_SERVER['REQUEST_METHOD'] = $method;  // Set request method .. 
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
        $conn_id = $this->_get_id();
        
        if( isset(self::$_conn_id[$conn_id]) )   // We need that function to prevent HMVC loops if someone use hmvc request
        {                   
            return $this;
        }         
    
        self::$_conn_id[$conn_id] = $conn_id;    // store connection id.   
        
        $URI    = base_register('URI');
        $router = base_register('Router');
        $config = base_register('Config');
        $output = base_register('Output');

        if($this->hmvc_connect === FALSE) 
        {
            $this->_set_response($router->hmvc_response);
            $this->_reset_router();
            
            return $this;
        } 
        
        $GLOBALS['d']   = $router->fetch_directory();   // Get requested directory
        $GLOBALS['s']   = $router->fetch_subfolder();   // Get requested subfolder
        $GLOBALS['c']   = $router->fetch_class();       // Get requested controller
        $GLOBALS['m']   = $router->fetch_method();      // Get requested method

        // a Hmvc uri must be unique otherwise may collission with standart uri.
        $URI->uri_string = '__HMVC_URI__'. $URI->uri_string; // .'/ID/'. $this->_get_id();
        $URI->cache_time = $this->cache_time ;
        
        ob_start();
        
        if($output->_display_cache($config, $URI, TRUE) !== FALSE) // Check request uri if there is a HMVC cached file exist.
        {       
            $cache_content = ob_get_contents();  @ob_end_clean();
            $this->_set_response($cache_content);
            
            $this->_reset_router();
            
            return $this;
        }
        
        @ob_end_clean();
        
        if($GLOBALS['s'] != '')  // sub folder request ?
        {
            $hmvc_uri = "{$GLOBALS['d']} / {$GLOBALS['s']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            // Check the sub controller exists or not
            if ( ! file_exists(DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT))
            {   
                $this->_set_response('Hmvc request not found: '.$hmvc_uri);

                $this->_reset_router();
                
                return $this;
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
                $this->_set_response('Hmvc unable to load your controller.Check your routes in Routes.php file is valid.');

                $this->_reset_router();
                
                return $this;
            }
            
            $controller = DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['c']. EXT;
            $arg_slice  = 3;
        }
        
        //------------------------------------
        $this->start_time = ob_request_timer('start');

        // Call the controller.
        require_once($controller);
        
        if ( ! class_exists($GLOBALS['c']) OR $GLOBALS['m'] == 'controller' 
              OR $GLOBALS['m'] == '_output'       
              OR $GLOBALS['m'] == '_hmvc_output'
              OR $GLOBALS['m'] == '_instance'
              OR in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods('Controller')))
            )
        {
            $this->_set_response('Hmvc request not found: '.$hmvc_uri);

            $this->_reset_router();
            
            return $this;
        }
        
        // If Everyting ok Declare Called Controller !
        $OB = new $GLOBALS['c']();

        // Check method exist or not
        if ( ! in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods($OB))))
        {
            $this->_set_response('Hmvc request not found: '.$hmvc_uri);

            $this->_reset_router();
            
            return $this;
        }
    
        ob_start();
        
        // Call the requested method.                1       2       3
        // Any URI segments present (besides the directory/class/method) 
        // will be passed to the method for convenience
        call_user_func_array(array($OB, $GLOBALS['m']), array_slice($URI->rsegments, $arg_slice)); 
         
        $content = ob_get_contents();
    
        @ob_end_clean();

        ob_start();
        
        // Write cache file if cache on ! and Send the final rendered output to the browser
        $output->_display_hmvc($content, $URI);
        
        $content = ob_get_contents();
        
        @ob_end_clean();
        
        $this->_set_response($content);
        
        $this->_reset_router();
                
        return $this;
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
        while (@ob_end_clean());  // clean all buffers
        
        $_SERVER = $_POST = $_GET = $_REQUEST = array();
        $_GET     = $this->_GET_BACKUP;           // Assign global variables we copied before ..
        $_POST    = $this->_POST_BACKUP;
        $_SERVER  = $this->_SERVER_BACKUP;
        $_REQUEST = $this->_REQUEST_BACKUP;
        
        // Set original objects foreach HMVC requests we backup before  ..
        
        $URI = base_register('URI');
        
        $this->_this->uri    = base_register('URI', $this->uri);
        $this->_this->router = base_register('Router', $this->router);
        $this->_this->config = base_register('Config', $this->config);
        
        this($this->_this);    // Set original $this to instance that we backup before
        
        $GLOBALS['d']   = $this->router->fetch_directory();   // Assign Original Router methods we copied before
        $GLOBALS['s']   = $this->router->fetch_subfolder();   
        $GLOBALS['c']   = $this->router->fetch_class();       
        $GLOBALS['m']   = $this->router->fetch_method();
        
        $this->clear();  // reset all HMVC variables.
        
        ++$this->request_count;     // Profiler 
    
        $end_time = ob_request_timer('end');
        
        $this->benchmark += $end_time - $this->start_time;
        $this->request_times[$URI->uri_string] = $end_time - $this->start_time;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set $_SERVER vars foreach hmvc
    * requests.
    * 
    * @param string $key
    * @param mixed  $val
    */
    public function set_server($key, $val)
    {
        $_SERVER[$key] = $val;
        
        $this->_set_conn_string($key.$val);
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
    * Get final hmvc output.
    * 
    * @return   string
    */
    public function response()
    {
        return $this->response;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Create HMVC connection string next 
    * we will convert it to connection id.
    * 
    * @param    mixed $id
    */
    private function _set_conn_string($id)
    {
        $this->_conn_string .= $id;
    }

    // --------------------------------------------------------------------
    
    /**
    * Convert connection string to HMVC
    * connection id.
    * 
    * @return   string
    */
    private function _get_id()
    {
        return md5(trim($this->_conn_string));
    }

}
// END HMVC Class

/* End of file HMVC.php */
/* Location: ./obullo/libraries/HMVC.php */