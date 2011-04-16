<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         obullo
 * @subpackage      Obullo.core
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2009.
 * @filesource
 * @license
 */

Class RouterException extends CommonException {}

 /**
 * Router Class
 * Parses URIs and determines routing
 *
 * @package     Obullo
 * @subpackage  obullo
 * @category    URI
 * @author      Ersin Guvenc
 * @version     0.1 changed php4 rules as php5
 * @version     0.2 Routing structure changed as /directory/class/method/arg..
 * @version     0.3 added query string support d= directory & c= class & m= method
 * @link
 */
Class OB_Router {

    public $uri;
    public $config;
    public $hmvc                = FALSE;
    public $hmvc_response       = '';
    public $routes              = array();
    public $error_routes        = array();
    public $class               = '';
    public $method              = 'index';
    public $directory           = '';
    public $subfolder           = '';
    public $uri_protocol        = 'auto';
    public $default_controller;

    /**
    * Constructor
    * Runs the route mapping function.
    *
    * @author   Ersin Guvenc
    * @version  0.1
    * @version  0.2 added config index method and include route
    */
    public function __construct()
    {
        $routes = get_config('routes');   // Obullo changes..

        $this->routes = ( ! isset($routes) OR ! is_array($routes)) ? array() : $routes;
        unset($routes);

        $this->method = $this->routes['index_method'];
        $this->uri    = core_register('URI');

        $this->_set_routing();

        log_me('debug', "Router Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
    * When we use HMVC we need to Clean
    * all data.
    *
    * @return  void
    */
    public function clear()
    {
        $this->uri                 = core_register('URI');   // reset cloned URI object.
        $this->config              = '';
        $this->hmvc                = FALSE;
        $this->hmvc_response       = '';
        // $this->routes           // route config shouln't be reset there cause some isset errors
        $this->error_routes        = array();
        $this->class               = '';
        $this->method              = 'index';
        $this->directory           = '';
        $this->subfolder           = '';
        $this->uri_protocol        = 'auto';
        $this->default_controller  = '';
    }

    // --------------------------------------------------------------------

    /**
    * Clone URI object for HMVC Requests, When we
    * use HMVC we use $this->uri = clone base_register('URI');
    * that means we say to Router class when Clone word used in HMVC library
    * use cloned URI object instead of orginal ( ersin ).
    */
    public function __clone()
    {
        $this->uri = clone $this->uri;
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
        if($this->hmvc == FALSE)    // GET request valid for standart router requests not HMVC.
        {
            // Are query strings enabled in the config file?
            // If so, we're done since segment based URIs are not used with query strings.
            if (config_item('enable_query_strings') === TRUE AND isset($_GET[config_item('controller_trigger')]))
            {
                $this->set_directory(trim($this->uri->_filter_uri($_GET[config_item('directory_trigger')])));

                if(isset($_GET[config_item('subfolder_trigger')]))
                {
                    // ( Obullo sub folder support )
                    $this->set_subfolder(trim($this->uri->_filter_uri($_GET[config_item('subfolder_trigger')])));
                }

                $this->set_class(trim($this->uri->_filter_uri($_GET[config_item('controller_trigger')])));

                if (isset($_GET[config_item('function_trigger')]))
                {
                    $this->set_method(trim($this->uri->_filter_uri($_GET[config_item('function_trigger')])));
                }

                return;
            }
        }

        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

        // Fetch the complete URI string
        $this->uri->_fetch_uri_string();

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri->uri_string == '')
        {
            if ($this->default_controller === FALSE)
            {
                if($this->hmvc)
                {
                    $this->hmvc_response = 'Hmvc unable to determine what should be displayed. A default route has not been specified in the routing file';
                    return FALSE;
                }
                else
                {
                    show_404('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
                }
            }

            // Turn the default route into an array.  We explode it in the event that
            // the controller is located in a subfolder
            $segments = $this->_validate_request(explode('/', $this->default_controller));

            if($this->hmvc)
            {
                if($segments === FALSE)
                {
                    return FALSE;
                }
            }

            $this->set_class($segments[1]);
            $this->set_method($this->routes['index_method']);  // index

            // Assign the segments to the URI class
            $this->uri->rsegments = $segments;

            // re-index the routed segments array so it starts with 1 rather than 0
            $this->uri->_reindex_segments();

            log_me('debug', "No URI present. Default controller set.");
            return;
        }
        
        unset($this->routes['default_controller']);

        // Do we need to remove the URL suffix?
        $this->uri->_remove_url_suffix();

        // Compile the segments into an array
        $this->uri->_explode_segments();

        // Parse any custom routing that may exist
        $this->_parse_routes();

        // Re-index the segment array so that it starts with 1 rather than 0
        $this->uri->_reindex_segments();
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
                $this->set_method($segments[2]);
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
        $this->uri->rsegments = $segments;
    }

    // --------------------------------------------------------------------

    /**
    * Validates the supplied segments.  Attempts to determine the path to
    * the controller.
    *
    * $segments[0] = module
    * $segments[1] = controller
    *
    *       0      1           2
    * module / controller /  method  /
    *       0      1           2           3
    * module / subfolder / controller /  method  /
    *
    * @author   Ersin Guvenc
    * @author   CJ Lazell
    * @access   private
    * @param    array
    * @version  Changed segments[0] as segments[1]
    *           added directory set to segments[0]
    * @return   array
    */
    public function _validate_request($segments)
    {
        if( ! isset($segments[0]) ) return $segments;
                                        
        $folder = 'controllers';
        
        if(defined('CMD') AND $this->hmvc == FALSE)  // Command Line Request
        {
            if(is_dir(MODULES .$segments[0]. DS .'tasks')) 
            {                   
                $folder = 'tasks'; 
            }
            else
            {
                array_unshift($segments, 'tasks');
            }
        }                 
                                        
        if (is_dir(MODULES . $segments[0]) OR defined('CMD'))  // Check module
        {
            $ROOT = MODULES;
            $this->set_directory($segments[0]);

            if( ! empty($segments[1]))
            {
                //----------- SUB FOLDER SUPPORT ----------//
                
                if(defined('CMD')) // if we have a APP task subfolder ? 
                {
                    if(is_dir(APP . 'tasks' .DS .$segments[1]))
                    {
                        $this->set_directory('');
                        
                        $ROOT   = rtrim(APP, DS);
                        $folder = 'tasks';
                    }
                }
                
                if(is_dir($ROOT . $this->fetch_directory() . DS .$folder. DS .$segments[1]))   // If there is a subfolder ?
                {
                    $this->set_subfolder($segments[1]);

                    if( ! isset($segments[2])) return $segments;

                    if (is_dir($ROOT .$this->fetch_directory(). DS .$folder. DS .$this->fetch_subfolder()))
                    {
                        if( file_exists($ROOT .$this->fetch_directory(). DS .$folder. DS .$this->fetch_subfolder(). DS .$this->fetch_subfolder(). EXT)
                            AND ! file_exists($ROOT .$this->fetch_directory(). DS .$folder. DS .$this->fetch_subfolder(). DS .$segments[2]. EXT))
                        {
                            array_unshift($segments, $this->fetch_directory());
                        }
                        
                        $segments[1] = $segments[2];      // change class
                        
                        if(isset($segments[3]))          
                        {
                            $segments[2] = $segments[3];  // change method
                        }

                        return $segments;
                    }

                //----------- SUB FOLDER SUPPORT END ----------//

                }
                else
                {
                    if(defined('CMD'))  // otherwise if we have just APP task controller ? 
                    {
                        if(file_exists(APP .'tasks'. DS .$segments[1]. EXT))
                        {
                            $this->set_directory('');
                        
                            $ROOT   = rtrim(APP, DS);
                            $folder = 'tasks';
                        }
                    }
                    
                    // echo $ROOT .$this->fetch_directory(). DS .$folder. DS .$segments[1]. EXT;
                    
                    if (file_exists($ROOT .$this->fetch_directory(). DS .$folder. DS .$segments[1]. EXT))
                    {
                        return $segments; 
                    }
                }
            }

            if(defined('CMD'))  // otherwise if we have just APP task controller ? 
            {
                if(file_exists(APP .'tasks'. DS .$segments[1]. EXT))
                {
                    $this->set_directory('');
                
                    $ROOT   = rtrim(APP, DS);
                    $folder = 'tasks';
                }
            }
            
            // Merge Segments
            if (file_exists($ROOT .$this->fetch_directory(). DS .$folder. DS .$this->fetch_directory(). EXT))
            {
                array_unshift($segments, $this->fetch_directory());

                if( empty($segments[2]) )
                {
                    $segments[2] = $this->routes['index_method'];
                }

                return $segments;
            }

        }

        if($this->hmvc)
        {
            $this->hmvc_response = 'Hmvc request not found.';

            return FALSE;
        }
        else
        {        
            show_404(); // security fix.
        }
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
            $this->_set_request($this->uri->segments);
            return;
        }

        // Turn the segment array into a URI string
        $uri = implode('/', $this->uri->segments);

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
        $this->_set_request($this->uri->segments);
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
    public function set_method($method)
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

    // --------------------------------------------------------------------
    
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
    * Fetch the directory (if any) that contains the requested controller class
    *
    * @access    public
    * @return    string
    */
    public function fetch_directory()
    {
        return $this->directory;
    }
    
    // --------------------------------------------------------------------

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
    
    // --------------------------------------------------------------------

    /**
    * A pretty HMVC forward using router class.
    * Easy to use, just support $_POST, $_GET and $_REQUEST super globals.
    * default $_GET.
    * 
    * $this->router->forward('welcome/test/hello');
    * 
    * @author   Ersin Guvenc
    * @param mixed   $uri     Hmvc uri
    * @param mixed   $method  Request method
    * @param boolean $no_loop Prevent hmvc loop in Global Controllers.  
    */
    public function forward($uri, $method = 'GET', $no_loop = FALSE)
    {
        loader::helper('ob/request');
        
        $params = $_GET;
        if($method == 'POST')
        {
            $params = $_POST;
        }
    
        return request($method, $uri, $params)->no_loop($no_loop)->exec()->response();
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Check Router Request Is Ajax.
    * 
    * @return boolean
    */
    public function is_ajax()
    {
        $http_request = i_server('HTTP_X_REQUESTED_WITH');
        
        if($http_request == 'XMLHttpRequest')
        {
            return TRUE;
        }
        
        return FALSE;
    }
    
}
// END Router Class

/* End of file Router.php */
/* Location: ./obullo/libraries/Router.php */
