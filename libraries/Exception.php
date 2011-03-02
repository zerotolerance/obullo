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
                      
        if(defined('CMD'))  // If Command Line Request. 
        {
            echo $type .': '. $e->getMessage(). ' File: ' .$e->getFile(). ' Line: '. $e->getLine(). "\n";
            
            log_me('error', 'Php Error Type (Cmd): '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE); 
            
            return;
        }
        
        loader::base_helper('view');
        
        $data['e']    = $e;
        $data['sql']  = $sql;
        $data['type'] = $type;
                                                                          
        ob_start();
        echo _load_view(APP .'core'. DS .'errors'. DS, 'ob_exception', $data, true);
        $buffer = ob_get_contents();
        ob_get_clean();
                       
        // Shutdown Errors
        //------------------------------------------------------------------------ 
        
        $errors['1']     = 'E_ERROR';             // ERROR
        $errors['4']     = 'E_PARSE';             // PARSE ERROR
        $errors['64']    = 'E_COMPILE_ERROR';     // COMPILE ERROR
        $errors['256']   = 'E_USER_ERROR';        // USER FATAL ERROR   
        
        // User Friendly Php Errors
        //------------------------------------------------------------------------ 
        
        $errors['2']     = 'E_WARNING';           // WARNING
        $errors['8']     = 'E_NOTICE';            // NOTICE
        $errors['16']    = 'E_CORE_ERROR';        // CORE ERROR
        $errors['32']    = 'E_CORE_WARNING';      // CORE WARNING
        $errors['128']   = 'E_COMPILE_WARNING';   // COMPILE WARNING
        $errors['512']   = 'E_USER_WARNING';      // USER WARNING
        $errors['1024']  = 'E_USER_NOTICE';       // USER NOTICE
        $errors['2048']  = 'E_STRICT';            // STRICT ERROR
        $errors['4096']  = 'E_RECOVERABLE_ERROR'; // RECOVERABLE ERROR
        $errors['8192']  = 'E_DEPRECATED';        // DEPRECATED ERROR
        $errors['16384'] = 'E_USER_DEPRECATED';   // USER DEPRECATED ERROR
        $errors['30719'] = 'E_ALL';               // ERROR
        
        $errors['OB_1923'] = 'OB_EXCEPTION';      // OBULLO EXCEPTIONAL ERRORS
        
        log_me('error', 'Php Error Type: '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE); 
                              
        $code  = $e->getCode();
        $level = config_item('error_reporting');
    
        $error = (isset($errors[$code])) ? $errors[$code] : 'OB_EXCEPTION';
         
        if(is_numeric($level)) 
        {
            switch ($level) 
            {              
               case -1: return; break; 
               case  0: return; break; 
               case  1: echo $buffer;  return; break;
            }   
        }       
                         
        $rules = $this->parse_regex($level);
        // var_dump($rules);
        if($rules == FALSE) return;
       
        if(count($rules['IN']) > 0)
        {
            $allow_errors = $rules['IN'];
            $allowed_errors = array();
                                      
           if(in_array('E_ALL', $rules['IN'], true))
           {                          
               $allow_errors   = array_unique(array_merge($rules['IN'], array_values($errors)));
           }
           
           if(count($rules['OUT']) > 0)
           {
               foreach($rules['OUT'] as $out_val)
               {
                   foreach($allow_errors as $in_val)
                   {
                       if($in_val != $out_val)
                       {
                           $allowed_errors[] = $in_val;
                       }
                    }
                }
           }
           
           unset($allow_errors);
        }
        
       if(in_array($error, $allowed_errors, TRUE)) { echo $buffer; }
    }
    
    //------------------------------------------------------------------------
    
    /**
    * Parse php native error notations 
    * e.g. E_NOTICE | E_WARNING
    * 
    * @author Ersin Guvenc
    * @param  mixed $string
    * @return array
    */
    public function parse_regex($string)
    {
        if(strpos($string, '(') > 0)  // (E_NOTICE | E_WARNING)     
        {
            if(preg_match('/\(.*?\)/s', $string, $matches))
            {
               $rule = str_replace(array($matches[0], '^'), '', $string);
               
               $data = array('IN' => trim($rule) , 'OUT' => rtrim(ltrim($matches[0], '('), ')'));
            }
        }
        elseif(strpos($string, '^') > 0) 
        {
            $items = explode('^', $string);
            
            $data = array('IN' => trim($items[0]) , 'OUT' => trim($items[1])); 
        }
        elseif(strpos($string, '|') > 0)
        {
            $data = array('IN' => array(trim($string)), 'OUT' => array());
        }
        else
        {                        
            $data = array('IN' => array(trim($string)), 'OUT' => array());
        }
        
        if(isset($data['IN']))
        {
            if(strpos($data['IN'], '|') > 0)
            {
                $data['IN'] = explode('|', $data['IN']);    
            }
            else
            {
                $data['IN'] = array($data['IN']);
            }
            
            if(strpos($data['OUT'], '^') > 0)
            {
                $data['OUT'] = explode('^', $data['OUT']); 
            }
            else
            {
                $data['OUT'] = array($data['OUT']);
            }
            
            $data['IN']  = array_map('trim', $data['IN']);
            $data['OUT'] = array_map('trim', $data['OUT']);
            
            return $data;
        }
        
        return FALSE;
    }
    
}
/* End of file Exception.php */
/* Location: ./obullo/libraries/Exception.php */