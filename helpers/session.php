<?php 
defined('BASE') or exit('Access Denied!');
 
/**
* Obullo Framework (c) 2010.
* Procedural Session Implementation With stdClass. 
* Less coding, Less Memory.
* 
* @author      Ersin Guvenc.
* 
*/
Class SessionException extends CommonException {}

if( ! isset($_ob->session)) 
{
    $_ob = load_class('Storage');
    $_ob->session = new stdClass();    // Create new Sesssion Object.
    
    $_ob->session->sess_encrypt_cookie  = FALSE;
    $_ob->session->sess_expiration      = '7200';
    $_ob->session->sess_match_ip        = FALSE;
    $_ob->session->sess_match_useragent = TRUE;
    $_ob->session->sess_cookie_name     = 'ob_session';
    $_ob->session->cookie_prefix        = '';
    $_ob->session->cookie_path          = '';
    $_ob->session->cookie_domain        = '';
    $_ob->session->sess_time_to_update  = 300;
    $_ob->session->encryption_key       = '';
    $_ob->session->flashdata_key        = 'flash';
    $_ob->session->time_reference       = 'time';
    $_ob->session->gc_probability       = 5;
    $_ob->session->sess_id_ttl          = '';
    $_ob->session->userdata             = array();
}
 
/**
* Be carefull you shouldn't declare sess_start
* function more than one time, but don't worry
* it will return to false automatically !!
* 
* @see Chapter / Helpers / Session Helper
* 
* @author   Ersin Guvenc
* @param    mixed $params
* @version  0.1
* @version  0.2  added extend support for driver files.
*/
if( ! function_exists('sess_start')) 
{
    function sess_start($params = array())
    {   
        static $session_start = NULL;
        
        if ($session_start == NULL)
        {
            $driver = (isset($params['sess_driver'])) ? $params['sess_driver'] : config_item('sess_driver');
            
            // Driver extend support
            $prefix      = config_item('subhelper_prefix');
            $driver_file = APP .'helpers'. DS .'drivers'. DS .'session'. DS .$prefix. $driver.'_driver'. EXT;
            
            if(file_exists($driver_file))
            {
                require($driver_file);
                loader::$_base_helpers[$prefix . $driver.'_driver'] = $prefix . $driver.'_driver';
            }
            
            require(BASE .'helpers'. DS .'drivers'. DS .'session'. DS .$driver.'_driver'. EXT);
            loader::$_base_helpers[$driver.'_driver'] = $driver.'_driver';

            _sess_start($params);
            $session_start = TRUE;
            
            return TRUE;
        }
        
        log_me('debug', "Sessions started"); 
        
        return FALSE;
    }
}

/* End of file session.php */
/* Location: ./obullo/helpers/session.php */
