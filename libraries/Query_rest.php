<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @filesource
 * @license
 */

Class RestException extends CommonException {}  
 
// ------------------------------------------------------------------------

/**
 * Rest Service Class for Ob Query
 *
 * @package       Obullo
 * @subpackage    Base
 * @category      Libraries
 * @author        CJ Lazell
 * @link        
 */
 
Class OB_Query_rest
{
    protected $format;              // json, xml, html, php
    protected $class;               // This is the class of the current controller
    protected $rest_format = NULL;
    
    protected $supported_formats = array(
    'json'      => 'application/json',
    'xml'       => 'application/xml',
    'rawxml'    => 'application/xml',
    'serialize' => 'text/plain',
    'php'       => 'text/plain',
    'html'      => 'text/html',
    'csv'       => 'application/csv',
    );
    
    public $args;       // This contains all our GET variables
    public $method;     // POST, DELETE, GET, PUT?

    public $is_public     = FALSE;   // Is the controller public or does it require basic or digest auth
    public $is_authorized = FALSE;
    public $queries       = '';
    public $sql_compiler  = '';
    public $request_type  = '';
    public $_REQUEST      = '';
    
    /**
    * Construct - Build Settings
    * 
    */
    public function __construct()
    {
        $this->request_type = config_item('ob_query_request_type');
        
        $this->class = strtolower(get_class($this));

        // Store the method i.e. get, pos, put, delete
        $this->method = $this->format = $this->_detect_method();

        // Grab what's in the URL
        $this->args = array_merge(array(this()->uri->segment(2) =>'index') , this()->uri->uri_to_assoc(3));

        switch($this->method)  // Grab _REQUEST data
        {
            case "GET":
            case "POST":
            case 'DELETE':
            case "PUT":
            if($this->_request_type == 'HMVC' || $this->_request_type == 'CURL' AND $this->_method != 'PUT')
            {
                $this->_REQUEST = $_REQUEST; // Store the request data sent to protect original
            }
            else
            {
                parse_str(file_get_contents('php://input'), $this->_REQUEST);
            }
            break;
        }

        if(isset($this->_REQUEST['q'])) // Set shortcut
        {
            $this->_REQUEST['query'] = $this->_REQUEST['q'];
        }

        if(isset($this->_REQUEST['query']))  // This is where we process the query string.
        {
            $query = $this->_REQUEST['query'];
        
            $f = $query{0};                 // First Letter
            $l = $query{strlen($query)-1};  // Last Letter
            
            if($f != '"' AND $f != '[' AND $f != '{') 
            {
                $query = '"'. $query;
            }
          
            if($l != '"' AND $l != ']' AND $l != '}') 
            {
                $query .= '"';
            }
            
            $this->queries = json_decode($query, TRUE);
            
            unset($this->_REQUEST['query']);
        }

        $this->rest_auth = isset($this->_REQUEST['auth_type']) ? $this->_REQUEST['auth_type'] : 'digest';

        // @todo We need to write SQL Compiler Drivers to other Database Intarfaces.
        // ------------------------------------------------------------------------
        
        $this->sql_compiler = new Query_Mysql_Compiler();
        $this->sql_compiler->init($this);
        
        // ------------------------------------------------------------------------
        
        if($this->rest_auth == 'basic')
        {
            $this->_prepare_basic_auth();
        }
        elseif($this->rest_auth == 'digest')
        {
            $this->_prepare_digest_auth();
        }
        
        $this->format = $this->_detect_format();  // Check the format the user wants i.e. json
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * @todo
    * 
    */
    public function _prepare_basic_auth()
    {

    }
    
    // ------------------------------------------------------------------------

    /**
    * @todo
    * 
    */
    public function _prepare_digest_auth()
    {
          
    }
    
    // ------------------------------------------------------------------------

    /**
    * Takes pure data and optionally a status code, 
    * then creates the response
    * 
    * @param mixed $data
    * @param mixed $http_code
    */
    public function response($data = '', $http_code = 200)
    {
        $ob = this();
        
        $ob->output->set_status_header($http_code);
        
        // If the format method exists, call and return the output in that format
        if(method_exists($this, '_' . $this->format))
        {
            if($this->request_type != 'HMVC')  // Set a Response header
            {
                $ob->output->set_header('Content-type: '. $this->supported_formats[$this->format]);
                
                $formatted_data = $this->{'_'.$this->format}($data);
                
                $ob->output->set_output($formatted_data);
            }
            else   // Format not supported, output directly
            {
                $ob->output->set_output($data);
            }
        }
    }
    
    // ------------------------------------------------------------------------
   
    /**
    * Detect which format should be used to output the data
    *  
    */
    protected function _detect_format()
    {
        $ob = this();
        
        loader::config('../ob_query/query_rest');
        
        // A format has been passed in the URL and it is supported
        
        if(array_key_exists('format', $this->args) AND array_key_exists($this->args['format'], $this->supported_formats))
        {
            return $this->args['format'];
        }
        
        // Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
        if($ob->config->item('rest_ignore_http_accept') === FALSE AND i_server('HTTP_ACCEPT'))
        {
            foreach(array_keys($this->supported_formats) as $format)    // Check all formats against the HTTP_ACCEPT header
            {
                if(strpos(i_server('HTTP_ACCEPT'), $format) !== FALSE)  // Has this format been requested ? 
                {
                    // If not HTML or XML assume its right and send it on its way
                    
                    if($format != 'html' AND $format != 'xml')
                    {
                        return $format;
                    }
                    else  // HTML or XML have shown up as a match
                    {
                        // If it is truely HTML, it wont want any XML
                        if($format == 'html' AND strpos(i_server('HTTP_ACCEPT'), 'xml') === FALSE)
                        {
                            return $format;
                        
                        // If it is truely XML, it wont want any HTML
                        } 
                        elseif($format == 'xml' AND strpos(i_server('HTTP_ACCEPT'), 'html') === FALSE)
                        {
                            return $format;
                        }
                    }
                }
            }

        }  // End HTTP_ACCEPT checking

        if($this->rest_format != NULL) // Well, none of that has worked! Let's see if the controller has a default
        {
            return $this->rest_format;
        }

        // Just use whatever the first supported type is, nothing else is working!
        list($default) = array_keys($this->supported_formats);
        
        return $default;
    }

    // ------------------------------------------------------------------------
    
    /**
    * Detect which method (POST, PUT, GET, DELETE) is being used
    * 
    * @return string
    */
    protected function _detect_method()
    {
        $method = strtoupper(i_server('REQUEST_METHOD'));
        
        if(in_array($method, array('GET', 'DELETE', 'POST', 'PUT')))
        {
            return $method;
        }
        
        return 'GET';
    }
    
    // ------------------------------------------------------------------------

    /**
    * Format XML for output
    * 
    * @param mixed $data
    * @param object $structure
    * @param mixed $basenode
    * 
    * @return string
    */
    protected function _xml($data = array(), $structure = NULL, $basenode = 'xml')
    {
        if($structure == NULL)
        {
            $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
        }
        
        // loop through the data passed in.
        foreach($data as $key => $value)
        {
            // no numeric keys in our xml please!
            
            if (is_numeric($key))
            {
                // make string key...
                //$key = "item_". (string) $key;
                
                $key = "item";
            }
            
            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z]/i', '', $key);

            // if there is another array found recrusively call this function
            if (is_array($value))
            {
                $node = $structure->addChild($key);
                
                // recrusive call.
                $this->_xml($value, $node, $basenode);
            }
            else
            {
                // add single node.
                $value = htmlentities($value, ENT_NOQUOTES, "UTF-8");
                $used_keys[] = $key;
                
                $structure->addChild($key, $value);
            }
        }
        
        // pass back as string. or simple xml object if you want!
        
        return $structure->asXML();
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Format Raw XML for output
    * 
    * @param mixed $data
    * @param object $structure
    * @param mixed $basenode
    * 
    * @return string
    */
    protected function _rawxml($data = array(), $structure = NULL, $basenode = 'xml')
    {
        if($structure == NULL)
        {
            $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
        }
        
        // loop through the data passed in.
        foreach($data as $key => $value)
        {
            // no numeric keys in our xml please!
            if(is_numeric($key))
            {
                $key = "item";
            }

            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z0-9_-]/i', '', $key);

            // if there is another array found recrusively call this function
            if (is_array($value))
            {
                $node = $structure->addChild($key);
                
                // recrusive call.
                $this->_xml($value, $node, $basenode);
            }
            else
            {
                // add single node.
                $value = htmlentities($value, ENT_NOQUOTES, "UTF-8");
                $used_keys[] = $key;
                
                $structure->addChild($key, $value);
            }
        }
        
        // pass back as string. or simple xml object if you want!
        
        return $structure->asXML();
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Format HTML for output
    * 
    * @param array $data
    */
    protected function _html($data = array())
    {
        if(isset($data[0]))  // Multi-dimentional array
        {
            $headings = array_keys($data[0]);
        }
        else  // Single array
        {
          $headings = array_keys($data);
          $data     = array($data);
        }
        
        $table = lib('table');

        $table->set_heading($headings);

        foreach($data as &$row)
        {
            $table->add_row($row);
        }
        
        return $table->generate();
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Format CSV for output
    * 
    * @param array $data
    */
    protected function _csv($data = array())
    {
        if(isset($data[0]))   // Multi-dimentional array
        {
            $headings = array_keys($data[0]);
        }
        else  // Single array
        {
            $headings = array_keys($data);
            $data = array($data);
        }
        
        $output = implode(',', $headings)."\r\n";
        
        foreach($data as &$row)
        {
            $output .= '"'.implode('","',$row)."\"\r\n";
        }
        
        return $output;
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Format Json for output
    * 
    * @param array $data
    */
    protected function _json($data = array())
    {
        echo json_encode($data);
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Format Serialize() string for output
    * 
    * @param array $data
    */
    protected function _serialize($data = array())
    {
        return serialize($data);
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Format raw PHP for output
    * 
    * @param array $data
    */
    protected function _php($data = array())
    {
        return var_export($data, TRUE);
    }

}

/* End of file Query_rest.php */
/* Location: ./obullo/libraries/Query_rest.php */