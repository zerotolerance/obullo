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
    public function write_exception($e, $type = '')
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
        include(ROOT . APP .'core'. DS .'errors'. DS .'ob_exception'. EXT);
        $buffer = ob_get_clean(); 

        log_me('error', 'Php Error Type: '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE);  
        
        if(config_item('display_errors'))  // If user want to display all errors
        {
            echo $buffer;
        }
    }

    
}

/* End of file Exception.php */
/* Location: ./obullo/libraries/Exception.php */