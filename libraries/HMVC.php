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
 * @category    HMVC - URI and Routers
 * @author      Ersin Guvenc
 * @version     0.1
 */
Class OB_HMVC
{
    // URI variables.. 
    public $keyval       = array();
    public $uri_string   = '';
    public $query_string = '';
    public $segments     = array();
    public $rsegments    = array();
    
    // Post and Get variables
    public $POST_keys    = array();
    public $GET_keys     = array();
    
    // Router variables.. 
    public $config;    
    public $routes              = array();
    public $error_routes        = array();
    public $class               = '';
    public $method              = 'index';
    public $directory           = '';
    public $subfolder           = '';
    public $response            = '';
    public $hmvc_connect        = TRUE;
    public $default_controller;
    
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
    public function hmvc_request($hmvc_uri = '', $cache_time = 0)
    {
        if($hmvc_uri != '')
        {
            $uri = base_register('URI');
            
            $this->cache_time = $cache_time;
            $this->uri_string = $uri->_filter_uri($hmvc_uri);   // secure URLS
            
            if(strpos($this->uri_string, '?') > 0)
            {
                $uri = explode('?', $this->uri_string);
                $this->uri_string   = $uri[0];
                $this->query_string = $uri[0] .'?'. $uri[1];
            }
            
            $routes = get_config('routes');
            
            $this->routes = ( ! isset($routes) OR ! is_array($routes)) ? array() : $routes;
            unset($routes);
            
            $this->method = $this->routes['index_method'];
            $this->hmvc_connect = $this->_set_routing();
            
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
        $this->cache_time   = 0;
        $this->segments     = array();
        $this->rsegments    = array();
        $this->class        = '';
        $this->method       = 'index';
        $this->directory    = '';
        $this->subfolder    = '';
        $this->post_keys    = array();
        $this->get_keys     = array();
        $this->reponse      = '';
        $this->hmvc_connect = TRUE;
        $this->default_controller  = '';    
    }
    
    // --------------------------------------------------------------------
    
    public function set_method($method = 'GET' , $params = array())
    {
        if($this->query_string != '')
        {
            $query_str_params = $this->parse_query($this->query_string);
            
            if(count($query_str_params) > 0) 
            {
                $params = array_merge($query_str_params, $params);
            }
        }
        
        switch ($method) 
        {
           case 'POST':
            foreach($params as $key => $val)
            {
                $_POST[$key] = $val;
                
                $this->POST_keys[$key] = '';
            }
             break;
             
           case 'GET':
           foreach($params as $key => $val)
            {
                $_GET[$key] = $val;
                
                $this->GET_keys[$key] = '';
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
        if($this->hmvc_connect === FALSE) 
        {
            return FALSE;
        } 
    
        $GLOBALS['d']   = $this->fetch_directory();   // Get requested directory
        $GLOBALS['s']   = $this->fetch_subfolder();   // Get requested subfolder
        $GLOBALS['c']   = $this->fetch_class();       // Get requested controller
        $GLOBALS['m']   = $this->fetch_method();      // Get requested method
        
        $config = base_register('Config');
        $output = base_register('Output');
    
        $URI = new stdClass();          // Create fake URI class.
        $URI->uri_string = '__HMVC_URI__'.$this->uri_string;
        $URI->cache_time = $this->cache_time;
        
        ob_start();    
    
        if($output->_display_cache($config, $URI, TRUE) == TRUE) // Check request uri if there is a HMVC cached file exist.
        {
            $response = ob_get_contents();
            @ob_end_clean();
            
            $this->_set_response($response);
            
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
        call_user_func_array(array($OB, $GLOBALS['m']), array_slice($this->rsegments, $arg_slice)); 
         
        $content = ob_get_contents();
        
        // Write cache file if cache on ! and Send the final rendered output to the browser
        $output->_display_hmvc($content, $URI);
        
        @ob_end_clean();
        
        //---------------------- Reset Variables ------------------//
        $this->_reset_router();
        
        $this->_set_response($content);
            
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
        @ob_end_clean(); // close buffer
        
        $this->clear();  // reset all variables.
                         // reset $GLOBALS
                         
        $router = base_register('Router');
        $GLOBALS['d']   = $router->fetch_directory();   // Get requested directory
        $GLOBALS['s']   = $router->fetch_subfolder();   // Get requested subfolder
        $GLOBALS['c']   = $router->fetch_class();       // Get requested controller
        $GLOBALS['m']   = $router->fetch_method();      // Get requested method
        
        
        // reset POST data foreach request
        foreach($this->POST_keys as $key => $val)
        {
            unset($_POST[$key]);
        }
        
        // reset GET data foreach request
        foreach($this->GET_keys as $key => $val)
        {
            unset($_GET[$key]);
        }
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

    // --------------------------------------------------------------------
    
    /**
    * Set the route mapping
    *
    * This function determines what should be served based on the URI request,
    * as well as any "routes" that have been set in the routing config file.
    *
    * @access    private
    * @author    Ersin Guvenc
    * @version   0.1
    * @return    void
    */
    public function _set_routing()
    {
        $ob = this();
        
        // Are query strings enabled in the config file?
        // If so, we're done since segment based URIs are not used with query strings.
        if (config_item('enable_query_strings') === TRUE AND isset($_GET[config_item('controller_trigger')]))
        {
            $this->set_directory(trim($ob->uri->_filter_uri($_GET[config_item('directory_trigger')])));
            
            // ( Obullo sub folder support )
            $this->set_subfolder(trim($ob->uri->_filter_uri($_GET[config_item('subfolder_trigger')])));
            
            $this->set_class(trim($ob->uri->_filter_uri($_GET[config_item('controller_trigger')])));

            if (isset($_GET[config_item('function_trigger')]))
            {
                $this->set_router_method(trim($ob->uri->_filter_uri($_GET[config_item('function_trigger')])));
            }

            return;
        }
        
        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);    
        
    
        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri_string == '')
        {
            if ($this->default_controller === FALSE)
            {
                $this->_set_response('HMVC Unable to determine what should be displayed. A default route has not been specified in the routing file.');
            
                return FALSE;
            }

            // Turn the default route into an array.  We explode it in the event that
            // the controller is located in a subfolder
            $segments = $this->_validate_request(explode('/', $this->default_controller)); 
        
            if($segments == FALSE) 
            {
                return FALSE;
            }
        
            // Set the class and method
            $this->set_class($segments[1]);
            $this->set_router_method($this->routes['index_method']);  // index
    
            // Assign the segments to the URI class
            $this->rsegments = $segments;
            
            // re-index the routed segments array so it starts with 1 rather than 0
            $this->_reindex_segments();
            
            log_me('debug', "No URI present. Default controller set.");
            return;
        }
        unset($this->routes['default_controller']);
        
        // Do we need to remove the URL suffix?
        $this->_remove_url_suffix();
        
        // Compile the segments into an array
        $this->_explode_segments();

        // Parse any custom routing that may exist
        $this->_parse_routes();        
        
        // Re-index the segment array so that it starts with 1 rather than 0
        $this->_reindex_segments();
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Validates the supplied segments.  Attempts to determine the path to
    * the controller.
    *
    * @author   Ersin Guvenc
    * @access   private
    * @param    array
    * @version  Changed segments[0] as segments[1]
    *           added directory set to segments[0]
    * @return   array
    */    
    public function _validate_request($segments)
    {
        // $segments[0] = directory
        // $segments[1] = controller name

        if( ! isset($segments[0]) ) $segments[0] = '';
        if( ! isset($segments[1]) ) $segments[1] = '';

        // Check directory
        if (is_dir(DIR . $segments[0]))
        {
            $this->set_directory($segments[0]);

            if( ! empty($segments[1]))
            {
                //----------- SUB FOLDER SUPPORT ----------//

                if(is_dir(DIR . $segments[0] . DS .'controllers'. DS .$segments[1]))   // If there is a subfolder ?
                {
                    //       0      1           2
                    // module / controller /  method  /
                    //       0      1           2           3
                    // module / subfolder / controller /  method  /
                    
                    $this->set_subfolder($segments[1]);

                    if( ! isset($segments[2])) return $segments;

                    if (is_dir(DIR .$segments[0]. DS .'controllers'. DS .$segments[1]))
                    {
                        if( file_exists(DIR .$segments[0]. DS .'controllers'. DS .$segments[1]. DS .$segments[1]. EXT)
                            AND ! file_exists(DIR .$segments[0]. DS .'controllers'. DS .$segments[1]. DS .$segments[2]. EXT)) 
                        {
                            array_unshift($segments, $segments[0]);
                        }
                                                  
                         $segments[1] = $segments[2];     // change class

                         if(isset($segments[3]))          // change method
                         {
                            $segments[2] = $segments[3];  
                         }

                        return $segments;
                    }

                //----------- SUB FOLDER SUPPORT END ----------//

                }
                else
                {
                    if (file_exists(DIR .$segments[0]. DS .'controllers'. DS .$segments[1]. EXT))
                    return $segments;
                }

            }

            /**
            * Merge Segments
            *
            * If you use a controller with the same name sd the folder
            * it will make that the route.
            * So instead of modulename/modulename/index it will be modulename/index
            *
            * @author CJ Lazell
            */
            if (file_exists(DIR .$segments[0]. DS .'controllers'. DS .$segments[0]. EXT))
            {
                array_unshift($segments, $segments[0]);

                if( empty($segments[2]) )
                {
                    $segments[2] = $this->routes['index_method'];
                }

                return $segments;
            }

        }

        $this->_set_response('HMVC uri not found: '. $segments[0].' / '.$segments[1]);
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
    * Parse Routes
    *
    * This function matches any routes that may exist in
    * the config/routes.php file against the URI to
    * determine if the class/method need to be remapped.
    *
    * @access    private
    * @return    void
    */
    public function _parse_routes()
    {
        // Do we even have any custom routing to deal with?
        // There is a default scaffolding trigger, so we'll look just for 1
        if (count($this->routes) == 1)
        {             
            $this->_set_request($this->segments);
            return;
        }

        // Turn the segment array into a URI string
        $uri = implode('/', $this->segments);

        // Is there a literal match?  If so we're done
        if (isset($this->routes[$uri]))
        {
            $this->_set_request(explode('/', $this->routes[$uri]));        
            return;
        }
                
        // Loop through the route array looking for wild-cards
        foreach ($this->routes as $key => $val)
        {                        
            // Convert wild-cards to RegEx
            $key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));
            
            // Does the RegEx match?
            if (preg_match('#^'.$key.'$#', $uri))
            {            
                // Do we have a back-reference?
                if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE)
                {
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }
            
                $this->_set_request(explode('/', $val));        
                return;
            }
        }
        
        // If we got this far it means we didn't encounter a
        // matching route so we'll set the site default route
        $this->_set_request($this->segments);
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set the Route
    *
    * This function takes an array of URI segments as
    * input, and sets the current class/method
    *
    * @access   private
    * @author   Ersin Guvenc
    * @param    array
    * @param    bool
    * @version  0.1
    * @version  0.2 Changed $segments[0] as $segments[1]  and 
    *           $segments[1] as $segments[2]
    * @return   void
    */
    public function _set_request($segments = array())
    {   
        $segments = $this->_validate_request($segments);
        
        if (count($segments) == 0)
        return;
                        
        $this->set_class($segments[1]);
        
        if (isset($segments[2]))
        {
                // A standard method request
                $this->set_router_method($segments[2]);   
        }
        else
        {
            // This lets the "routed" segment array identify that the default
            // index method is being used.
            $segments[2] = $this->routes['index_method'];
        }
        
        // Update our "routed" segment array to contain the segments.
        // Note: If there is no custom routing, this array will be
        // identical to $this->uri->segments
        $this->rsegments = $segments;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set the class name
    *
    * @access    public
    * @param     string
    * @return    void
    */    
    public function set_class($class)
    {
        $this->class = $class;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Fetch the current class
    *
    * @access    public
    * @return    string
    */    
    public function fetch_class()
    {
        return $this->class;
    }
    
    // --------------------------------------------------------------------
    
    /**
    *  Set the method name
    *
    * @access    public
    * @param     string
    * @return    void
    */    
    public function set_router_method($method)
    {
        $this->method = $method;
    }

    // --------------------------------------------------------------------
    
    /**
    *  Fetch the current method
    *
    * @access    public
    * @return    string
    */    
    public function fetch_method()
    {
        if ($this->method == $this->fetch_class())
        {
            return $this->routes['index_method'];
        }

        return $this->method;
    }

    // --------------------------------------------------------------------
    
    /**
    *  Set the directory name
    *
    * @access   public
    * @param    string
    * @return   void
    */    
    public function set_directory($dir)
    {
        $this->directory = $dir.'';  // Obullo changes..
    }

    /**
    *  Set the subfolder name
    *
    * @access   public
    * @param    string
    * @return   void
    */
    public function set_subfolder($dir)
    {
        $this->subfolder = $dir.'';  // Obullo changes..
    }

    // --------------------------------------------------------------------
    
    /**
    *  Fetch the sub-directory (if any) that contains the requested controller class
    *
    * @access    public
    * @return    string
    */    
    public function fetch_directory()
    {
        return $this->directory;
    }

    /**
    *  Fetch the sub-directory (if any) that contains the requested controller class
    *
    * @access    public
    * @return    string
    */
    public function fetch_subfolder()
    {
        return $this->subfolder;
    }
    
    // ------------------------ URI FUNCTIONS -----------------------------
    // --------------------------------------------------------------------

    /**
    * Remove the suffix from the URL if needed
    *
    * @access    private
    * @return    void
    */    
    public function _remove_url_suffix()
    {
        if  (config_item('url_suffix') != "")
        {
            $this->uri_string = preg_replace("|".preg_quote(config_item('url_suffix'))."$|", "", $this->uri_string);
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Explode the URI Segments. The individual segments will
     * be stored in the $this->segments array.    
     *
     * @access    private
     * @return    void
     */        
    public function _explode_segments()
    {
        $OB = this();
        
        foreach(explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val)
        {
            // Filter segments for security
            $val = trim($OB->uri->_filter_uri($val));
            
            if ($val != '')
            {
                $this->segments[] = $val;
            }
        }
    }
    
    // --------------------------------------------------------------------
      
    /**
     * Re-index Segments
     *
     * This function re-indexes the $this->segment array so that it
     * starts at 1 rather than 0.  Doing so makes it simpler to
     * use functions like $this->uri->segment(n) since there is
     * a 1:1 relationship between the segment array and the actual segments.
     *
     * @access    private
     * @return    void
     */    
    public function _reindex_segments()
    {
        array_unshift($this->segments, NULL);
        array_unshift($this->rsegments, NULL);
        unset($this->segments[0]);
        unset($this->rsegments[0]);
    }    
    
    // --------------------------------------------------------------------
    
    /**
     * Fetch a URI Segment
     *
     * This function returns the URI segment based on the number provided.
     *
     * @access   public
     * @param    integer
     * @param    bool
     * @return   string
     */
    public function segment($n, $no_result = FALSE)
    {
        return ( ! isset($this->segments[$n])) ? $no_result : $this->segments[$n];
    }
   
}
// END HMVC Class

/* End of file HMVC.php */
/* Location: ./obullo/libraries/HMVC.php */