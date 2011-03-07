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

// ------------------------------------------------------------------------

/**
 * Exception Class
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Exceptions
 * @author        Ersin Guvenc
 * @link
 */
Class OB_Exception {
    
    function __construct()
    {
        log_me('debug', "Exception Class Initialized");
    }
 
    /**
    * Display all errors
    * 
    * @param object $e
    * @param string $type
    * 
    * @return string
    */
    public function write($e, $type = '')
    {
        $type = ($type != '') ? ucwords(strtolower($type)) : 'Exception Error';
        $sql  = array();
        
        // Database Errors
        //-----------------------------------------------------------------------
        if(substr($e->getMessage(),0,3) == 'SQL') 
        {
            $ob   = this();
            $type = 'Database';
            
            foreach(profiler_get('databases') as $db_name => $db_var)
            {
               if(is_object($ob->$db_var))
               {
                   $last_query = $ob->{$db_var}->last_query($ob->{$db_var}->prepare);
                   
                   if( ! empty($last_query))
                   {
                       $sql[$db_name] = $last_query;
                   }
               }
            }        
        }
        
        // Command Line Errors
        //-----------------------------------------------------------------------
        if(defined('CMD'))  // If Command Line Request. 
        {
            echo $type .': '. $e->getMessage(). ' File: ' .$e->getFile(). ' Line: '. $e->getLine(). "\n";
            
            $cmd_type = (defined('TASK')) ? 'Task' : 'Cmd';
            
            log_me('error', 'Php Error Type ('.$cmd_type.'): '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE); 
            
            return;
        }
        
        // Load Error Template
        //-----------------------------------------------------------------------
        loader::base_helper('view');
        
        $data['e']    = $e;
        $data['sql']  = $sql;
        $data['type'] = $type;

        $error_msg = load_view(APP .'core'. DS .'errors'. DS, 'ob_exception', $data, true);
        
        // Log Php Errors
        //-----------------------------------------------------------------------
        log_me('error', 'Php Error Type: '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE); 
             
        // Displaying Errors
        //-----------------------------------------------------------------------                
        $code   = $e->getCode();
        $level  = config_item('error_reporting');
        $errors = error_get_defined_errors();
        $error  = (isset($errors[$code])) ? $errors[$code] : 'OB_EXCEPTION';
        
        $http_request = i_server('HTTP_X_REQUESTED_WITH');
         
        if(is_numeric($level)) 
        {
            switch ($level) 
            {              
               case -1: return; break; 
               case  0: return; break; 
               case  1:
               if($http_request == 'XMLHttpRequest')  // Ajax Friendly Errors Errors
               {
                   echo $type .': '. $e->getMessage(). ' File: ' .$e->getFile(). ' Line: '. $e->getLine(). "\n";    
               }
               else
               {
                   echo $error_msg;
               }   
               return; break;
            }   
        }       
                         
        $rules = error_parse_regex($level);
        
        if($rules == FALSE) 
        {
            return;
        }
        
        if(in_array($error, error_get_allowed_errors($rules), TRUE))
        { 
           if($http_request == 'XMLHttpRequest')  // Ajax Friendly Errors Errors
           {
               echo $type .': '. $e->getMessage(). ' File: ' .$e->getFile(). ' Line: '. $e->getLine(). "\n";    
           }
           else
           {
               echo $error_msg;
           }   
        }
    }

}
/* End of file Exception.php */
/* Location: ./obullo/libraries/Exception.php */