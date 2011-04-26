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

Class QueryException extends CommonException {}

// ------------------------------------------------------------------------

/**
 * Object Query Class
 *
 * Create SQL Query and Parse Models.
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Libraries
 * @author        CJ Lazell
 * @author        Ersin Guvenc
 * @link
 */
Class OB_Query {
    
    public $url            = '';
    public $request_type   = '';    // HMVC or CURL
    public $request_method = 'GET'; // Default GET or POST, PUT, DELETE
    public $request_length = 0;
    public $request_body   = NULL;
    public $response_body  = NULL;
    public $response_info  = NULL;
    public $accept_type    = 'application/json';
    public $data           = array();
    
    protected $username    = '';   // HTTP Auth
    protected $password    = '';
    
    /**
    * Construct Default Settings
    * 
    */
    public function __construct()
    {
        $this->url = base_url() . config_item('ob_query_service_url');
        $this->request_type   = config_item('ob_query_request_type');
        $this->request_method = 'GET';
                
        $this->username = isset($_REQUEST['username']) ? $_REQUEST['username'] == '' ? 'false' : $_REQUEST['username'] : '';
        $this->password = isset($_REQUEST['password']) ? md5($_REQUEST['password']) : '';
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Reset Variables
    * 
    */
    public function clear()
    {
        $this->request_method  = 'GET';
        $this->request_length  = 0;
        $this->request_body    = NULL;
        $this->response_body   = NULL;
        $this->response_info   = NULL;
        $this->accept_type     = 'application/json';
    }

    // --------------------------------------------------------------------
    
    /**
    * Run The Object Queries
    * 
    * @param mixed $method
    * @param string $query
    * @param mixed $is_object
    */
    public function exec($method = 'GET', $query = '', $is_object = FALSE)
    {
        $query = urlencode(json_encode($query));
        $multi_queries = is_array($query) ? TRUE : FALSE;
        
        $this->request_method = $method;    
        $this->set_var('query', $query);

        if($this->request_type == 'CURL')
        {
            $ch = curl_init();
            $this->set_auth($ch);
            
            try
            {
                switch (strtoupper($this->request_method))
                {
                    case 'GET':
                    $this->execute_get($ch);
                    break;
                    
                    case 'POST':
                    $this->execute_post($ch);
                    break;
                    
                    case 'PUT':
                    $this->execute_put($ch);
                    break;
                    
                    case 'DELETE':
                    $this->execute_delete($ch);
                    break;
                    
                    default:
                    throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST method.');
                }
            }
            catch (InvalidArgumentException $e)
            {
                curl_close($ch);
                throw $e;
            }
            catch (Exception $e)
            {
                curl_close($ch);
                throw $e;
            }
            
            $response = $this->get_response_body($is_object);
            
            $this->response = $response;   // HMVC - CURL switch
        }
        elseif($this->request_type == 'HMVC')
        {
            loader::helper('ob/request');
            
            $data = array();

            $request = request(strtoupper($this->request_method), $this->url, array_merge($this->data, $data))->no_loop();

            $this->response_body = $request->exec()->response();

            $response = $this->get_response_body($is_object);

            $this->response = $response;   // HMVC - CURL switch
        }

        /* DISABLED FOR SECURITY
        if(isset($_REQUEST['sql']) || isset($_REQUEST['benchmark']) || isset($_REQUEST['debug']))
        {
            $this->debug();
        }
        
        if(isset($_REQUEST['__TEST__']))
        {
            echo $this->get_response_body();
        }
        */
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set Ob Query Variables before the
    * process.
    * 
    * @param string $name
    * @param mixed $value
    */
    public function set_var($name, $value)
    {
        $is_set = FALSE;
        $name   = strtolower($name);
        
        switch($name)
        {
            case 'requests':
            if($this->request_method == 'GET')
            {
                foreach($value as $id => $data)
                {
                    if($data == '') continue;
                    
                    if($id != 'PHPSESSID' AND $id != config_item('sess_cookie_name') AND $id != 'APE_Cookie') 
                    {
                        $this->set_var($id, $data);
                    }
                }
            }
            else 
            {
                foreach($value as $id => $data)
                {
                    if($id != 'PHPSESSID' AND $id != config_item('sess_cookie_name') AND $id != 'APE_Cookie' $id != 'file') 
                    {
                        $this->data[$id] = $data;
                    }
                    elseif($id == 'file')
                    {
                        $this->data[$id]  = $value[$id];
                    }
                }
            }
            
            $is_set = TRUE;
            break;
        }
        
        if( ! $is_set AND ( $this->request_method == 'GET' OR $this->request_method == 'DELETE' ))
        {
            $url_string     = explode('?', $this->url);
            $new_url_string ='&'. $name .'=';
            
            if(is_array($value))
            {
                foreach($value as $t => $new_val)
                {
                    if(is_string($t))
                    {
                        $new_url_string .= ' '. $t;
                        foreach($value[$t] as $key => $field)
                        {
                            if(is_array($field))
                            {
                                $new_url_string .= ' '. $t .'-'. $key;
                                foreach($field as $sub_value)
                                {
                                    $new_url_string .= ' '. $sub_value;
                                }
                            }
                            else
                            {
                                $new_url_string .= ' '. $field;
                            }
                        }
                    }
                    else
                    {
                        $new_url_string .= ($t != 0 ? ' ' : '') . $value[$t];
                    }
                }
            }
            else
            {
                $new_url_string .= $value;
            }
            
            $new_url_string = (isset($url_string[1]) ? '&'. $url_string[1] : '') . $new_url_string;
            
            $this->url = preg_replace('#&#', '?' , $url_string[0] . $new_url_string, 1);
        }
        
        if($name != 'requests') 
        {
            $this->data[$name] = $value;
        }
    }

    // --------------------------------------------------------------------

    /**
    * Build POST Body
    * 
    * @param mixed $data
    */
    public function build_post_body ($data = NULL)
    {
        $this->data = ($this->data !== NULL) ? $this->data : $this->request_body;
        
        if ( ! is_array($this->data))
        {
            throw new InvalidArgumentException('Invalid data input for post body. Array expected !');
        }
        
        $this->data = http_build_query($this->data, '', '&');
        $this->request_body = $this->data;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * CURL Execute GET method
    * 
    * @param mixed $ch
    */
    protected function execute_get ($ch)
    {
        $this->do_execute($ch);
    }
    
    // --------------------------------------------------------------------
    
    /**
    * CURL Execute POST method
    * 
    * @param mixed $ch
    */
    protected function execute_post ($ch)
    {
        if ( ! is_string($this->request_body))
        {
            $this->build_post_body();
        }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request_body);
        curl_setopt($ch, CURLOPT_POST, 1);

        $this->do_execute($ch);
    }

    // --------------------------------------------------------------------
    
    /**
    * CURL Execute PUT method
    * 
    * @param mixed $ch
    */
    protected function execute_put ($ch)
    {
        if ( ! is_string($this->request_body))
        {
            $this->build_post_body();
        }
        
        $this->request_length = strlen($this->request_body);
        
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $this->request_body);
        rewind($fh);
        
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $this->request_length);
        curl_setopt($ch, CURLOPT_PUT, TRUE);
        
        $this->do_execute($ch);
        
        fclose($fh);
    }

    // --------------------------------------------------------------------
    
    /**
    * CURL Execute PUT method
    * 
    * @param mixed $ch
    */
    protected function execute_delete ($ch)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        
        $this->do_execute($ch);
    }
    
    // --------------------------------------------------------------------
     
    /**
    * Execute CURL
    * 
    * @param mixed $curl_handle
    */
    protected function do_execute (&$curl_handle)
    {
        $this->set_curl_opts($curl_handle);
        
        $this->response_body = curl_exec($curl_handle);
        $this->response_info = curl_getinfo($curl_handle);
        
        curl_close($curl_handle);
    }
    
    // --------------------------------------------------------------------
    
    /**
    * CURL Set Options
    * 
    * @param mixed $curl_handle
    */
    protected function set_curl_opts (&$curl_handle)
    {
        curl_setopt($curl_handle, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl_handle, CURLOPT_URL, str_replace(' ','%20',$this->url));
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->acceptType));
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set Auth for CURL
    * 
    * @param mixed $curl_handle
    */
    protected function set_auth (&$curl_handle)
    {
        if ($this->username !== NULL && $this->password !== NULL)
        {
            curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($curl_handle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }
    }

    // --------------------------------------------------------------------

    /**
    * GET Accepted Format
    * 
    */
    public function get_accept_type ()
    {
        return $this->accept_type;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * put your comment there...
    * 
    * @param mixed $acceptType
    */
    public function set_accept_type ($acceptType)
    {
        $this->accept_type = $acceptType;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Get CURL Password
    * 
    */
    public function get_password ()
    {
        return $this->password;
    }
    
    // --------------------------------------------------------------------

    /**
    * Get CURL Username
    * 
    * @param mixed $password
    */
    public function set_password ($password)
    {
        $this->password = $password;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Decode and Fetch Response Body
    * 
    * @param mixed $decode
    * @return mixed
    */
    public function get_response_body ($decode = FALSE)
    {
        $response_header = $this->get_response_info();
        
        if($decode)
        {
            $response_body = json_decode($this->response_body, TRUE);
        }
        else 
        {
            $response_body = $this->response_body;
        }
        
        return $response_body;
    }
    
    // --------------------------------------------------------------------

    /**
    * put your comment there...
    * 
    */
    public function get_response_info ()
    {
        return $this->response_info;
    }
    
    // --------------------------------------------------------------------
    
    public function get_url ()
    {
        return $this->url;
    }
    
    // --------------------------------------------------------------------
    
    public function set_url ($url)
    {
        $this->url = $url;
    }
    
    // --------------------------------------------------------------------
    
    public function get_username ()
    {
        return $this->username;
    }
    
    // --------------------------------------------------------------------
    
    public function set_username ($username)
    {
        $this->username = $username;
    }
    
    // --------------------------------------------------------------------
    
    public function get_method ()
    {
        return $this->request_method;
    }
    
    // --------------------------------------------------------------------
    
    public function set_method ($method = 'GET')
    {
        $this->request_method = $method;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Debug Response of Query
    * 
    */
    public function debug()
    {
        echo $this->url;
        
        if($this->request_method == 'CURL')
        {
            echo '<pre>' . print_r($this->get_response_body(), TRUE) . '</pre>';
            echo '<pre>' . print_r($this->get_response_body(TRUE), TRUE) . '</pre>';
            exit;
        }
        elseif($this->request_method == 'HMVC')
        {
            echo '<pre>' . print_r($this->get_response_body(), TRUE) . '</pre>';
            echo '<pre>' . print_r($this->get_response_body(TRUE), TRUE) . '</pre>';
        }
    }
    
}

/* End of file Query.php */
/* Location: ./obullo/libraries/Query.php */