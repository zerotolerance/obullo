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
    * Dsiplay all errors
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

        ob_start();
        include(APP .'core'. DS .'errors'. DS .'ob_exception'. EXT);
        $buffer = ob_get_contents();
        ob_clean();  // Don't close output buffering just clean it, 
                     // there is a blank page issue in some servers.
               
        if(config_item('error_reporting') > 0) 
        {
            return $buffer;   
        }
                     
        log_me('error', 'Php Error Type: '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE);  
        
        //@todo  ---  HMVC DISPLAY ERROR 
        // echo $buffer;
        
        /*        
        $errors['1']     = 'E_ERROR';             // ERROR
        $errors['2']     = 'E_WARNING';           // WARNING
        $errors['4']     = 'E_PARSE';             // PARSE ERROR
        $errors['8']     = 'E_NOTICE';            // NOTICE
        $errors['16']    = 'E_CORE_ERROR';        // CORE ERROR
        $errors['32']    = 'E_CORE_WARNING';      // CORE WARNING
        $errors['64']    = 'E_COMPILE_ERROR';     // COMPILE ERROR
        $errors['128']   = 'E_COMPILE_WARNING';   // COMPILE WARNING
        $errors['256']   = 'E_USER_ERROR';        // USER FATAL ERROR
        $errors['512']   = 'E_USER_WARNING';      // USER WARNING
        $errors['1024']  = 'E_USER_NOTICE';       // USER NOTICE
        $errors['2048']  = 'E_STRICT';            // STRICT ERROR
        $errors['4096']  = 'E_RECOVERABLE_ERROR'; // RECOVERABLE ERROR
        $errors['8192']  = 'E_DEPRECATED';        // DEPRECATED ERROR
        $errors['16384'] = 'E_USER_DEPRECATED';   // USER DEPRECATED ERROR
        $errors['30719'] = 'E_ALL';               // ERROR
        
        $code      = $e->getCode();
        $err_level = config_item('error_reporting');
        


        if(isset($errors[$code]))  // If user want to display all errors
        {
            $string = $errors[$code];
               
            switch ($err_level) 
            {
               case ($err_level == '' || $err_level == 0 || $err_level == NULL):
               echo 's'; exit();
               return;
                 break;
                
               case 1:
               echo 'dddddd';
               return $buffer;
                 break;
                 
               case 2:
               echo 'dddddd';
               $running_errors = array('E_ERROR', 'E_WARNING', 'E_PARSE', 'E_USER_ERROR');
               
               if(in_array($string, $running_errors, true))
               {
                   return $buffer;
               }
                 break;
                 
               case 3:
               echo 'dddddd';
               $running_errors = array('E_ERROR', 'E_WARNING', 'E_PARSE', 'E_USER_ERROR', 'E_NOTICE');
               
               if(in_array($string, $running_errors, true))
               {
                   return $buffer;
               }
            
                 break;
               
               case 4:
            
                 break;
                 
               case 5:
            
                 break;
                 
               case 6:
            
                 break;
            }
        }         
        */
        
    } // end func.
    
}

/* End of file Exception.php */
/* Location: ./obullo/libraries/Exception.php */