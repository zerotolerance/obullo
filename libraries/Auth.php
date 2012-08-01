<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2012.
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
 * Auth Class
 *
 * A lightweight and simple auth control class.
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Libraries
 * @author        Obullo
 * @link
 */

Class OB_Auth {
   
    public $session_prefix     = 'auth_';
    public $db_var             = 'db';   // database connection variable
    public $tablename          = '';     // The name of the database tablename
    public $username_col       = 'username';  // The name of the table field that contains the username.
    public $password_col       = 'password';  // The name of the table field that contains the password.
    public $md5                = TRUE;        // Whether to use md5 hash.
    public $allow_login        = TRUE;        // Whether to allow logins to be performed on this page.

    public $post_username      = '';     // The name of the form field that contains the username to authenticate.
    public $post_password      = '';     // The name of the form field that contains the password to authenticate.
    public $advanced_security  = FALSE;  // Whether to enable the advanced security features.
    public $query_binding      = FALSE;  // Whether to enable the PDO query binding feature for security.
    public $regenerate_sess_id = FALSE;  // Set to TRUE to regenerate the session id on every page load or leave as FALSE to regenerate only upon new login.
    
    public $row = FALSE;    // SQL Query result as row
    
    /**
    * Constructor
    *
    * Sets the variables and runs the compilation routine
    *
    * @version   0.1
    * @access    public
    * @return    void
    */
    public function __construct($params = array())
    {   
        $auth   = get_config('auth');        
        $config = array_merge($auth , $params);

        foreach($config as $key => $val)
        {
            $this->{$key} = $val;
        }
        
        loader::helper('ob/session');
        
        sess_start();

        $this->db = loader::database($this->db_var, TRUE);
        
        log_me('debug', "Auth Class Initialized");
    }
    
    // ------------------------------------------------------------------------

    /**
    * Set database select names
    * 
    * @param string $select database select fields
    */
    public function select($select = 'username, password')
    {
        if(is_array($select))
        {
            $this->select_data = $data;
        } 
        else
        {
            $select = trim($select, ',');
            $this->select_data = explode(',', $select);
            $this->select_data = array_map('trim', $this->select_data);
        }
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Send post query to login
    * 
    * @param string $username  manually login username
    * @param string $password  manually login password
    * @return bool | object
    */
    public function get($username = '', $password = '')
    {
        if($this->item('allow_login') == FALSE)
        {
            return FALSE;
        }
        
        if( empty($username) AND empty($password) )
        {
            $username = i_get_post($this->post_username, $this->advanced_security);
            $password = i_get_post($this->post_password);            
        } 
        
        $username = trim($username);
        $password = trim($password);

        if($this->md5 AND ! $this->_is_md5($password))
        {
            $password = md5($password);
        }
        
        if($this->query_binding)         // High Secure Pdo Query
        {
            $this->db->prep();      
            $this->db->select(implode(',', $this->select_data));
            $this->db->where($this->username_col, ':username');
            $this->db->where($this->password_col, ':password');
            $this->db->get($this->tablename);

            $this->db->bind_param(':username', $username, param_str, $this->username_length); // String (int Length)
            $this->db->bind_param(':password', $password, param_str, $this->password_length); // String (int Length)

            $query = $this->db->exec();
            $this->row = $query->row();
        } 
        else 
        {
            $this->db->select(implode(',', $this->select_data));
            $this->db->where($this->username_col, $username);
            $this->db->where($this->password_col, $password);
            $query = $this->db->get($this->tablename);
            
            $this->row = $query->row();
        }
        
        if(is_object($this->row) AND isset($this->row->{$this->username_col}))
        {
            return $this->row;
        }
        
        return FALSE;
    }
    
    // ------------------------------------------------------------------------
    
    /**
     * Autheticate the user if login is successfull !
     * 
     * @return bool
     */
    public function set()
    {
        $row = $this->get_row();
        
        if(is_object($row) AND isset($row->{$this->username_col}))
        {            
            $this->set_auth($this->select_data);  // auth is ok ?
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    // ------------------------------------------------------------------------
    
    /**
     * Get validated user sql query result object
     *
     * @return type 
     */
    public function get_row()
    {
        return $this->row;
    }
    
    // ------------------------------------------------------------------------
    
    /**
     * Get User is authenticated 
     * if its ok it returns to TRUE otherwise FALSE
     * 
     * @param type $callback
     * @param type $params
     * @return boolean | void ( callback function )
     */
    public function check($success_url = '', $fail_url = '')
    {
        if(sess($this->session_prefix.'ok') == 1)  // auth is ok ?
        {
            if($success_url != '')
            {
                loader::helper('ob/url');
                
                redirect($success_url); 
            }
            
            return TRUE;
        }
        
        if($fail_url != '')
        {
            loader::helper('ob/url');

            redirect($fail_url); 
        }
        
        return FALSE;
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Retrieve authenticated user session data
    * 
    * @param type $key
    * @return type 
    */
    public function data($key)
    {
        return sess($this->session_prefix.$key);
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Set session auth data to user session container
    * 
    * @param string $key
    * @return void 
    */
    public function set_data($key)
    {
        sess_set($this->session_prefix.$key);
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Unset session auth data from user session container
    * 
    * @param string $key
    * @return void
    */
    public function unset_data($key)
    {
        sess_set($this->session_prefix.$key);
    }
    
    // ------------------------------------------------------------------------
    
    /**
     * Override to auth configuration.
     * 
     * @param string $key
     * @param mixed $val 
     */
    public function set_item($key, $val)
    {
        $this->{$key} = $val;
    }
    
    //-------------------------------------------------------------------------
    
    /**
     * Get auth config item.
     * 
     * @param string $key
     * @return mixed
     */
    public function item($key)
    {
        return $this->{$key};
    }
    
    
    public function set_expire() {}
    public function set_idle() {}
    
    // ------------------------------------------------------------------------
    
    /**
     * Check password is md5.
     * 
     * @access private
     * @param string $md5
     * @return boolean 
     */
    public function _is_md5($md5)
    {
        if(empty($md5)) 
        {
            return FALSE;
        }
        
        return preg_match('/^[a-f0-9]{32}$/', $md5);
    }
        
    // ------------------------------------------------------------------------
    
    /**
     * Store auth data to session container.
     * 
     * @param array $data 
     * @return void
     */
    public function set_auth($data = array())
    {
        $row = $this->get_row();
        
        sess_set($this->session_prefix.'ok', 1);  // Authenticate the user.
        
        $sess_data = array();
        foreach($data as $key)
        {
            $sess_data[$this->session_prefix.$key] = $row->{$key};
        }
        
        sess_set($sess_data);   // Store user data to session container.
    }
    
    // ------------------------------------------------------------------------
    
    /**
    * Logout user and destroy session auth data.
    * 
    * @param bool $sess_destroy whether to use session destroy function
    * @return void 
    */
    public function logout($sess_destroy = FALSE)
    {
        sess_unset($this->session_prefix.'ok');
        
        if($sess_destroy)
        {
            sess_destroy();
            return;
        }
        
        $user_data = sess_alldata();
        if(count($user_data) > 0)
        {
            foreach($user_data as $key => $val)
            {
                if(strpos($key, $this->session_prefix) === 0)
                {
                    sess_unset($key);
                }
            }
        }
    }
    
}

// END Auth Class

/* End of file Auth.php */
/* Location: ./obullo/libraries/Auth.php */