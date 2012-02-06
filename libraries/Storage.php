<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo    
 * @subpackage      Obullo.core    
 * @author          obullo.com
 * @copyright       Obullo Team
 * @since           Version 1.0
 * @filesource
 * @license
 */
 
/**
* A Storage Class (c) 2011.
* Control the Procedural Functions.
* User can use this class to set custom Objects.
* 
* @version  0.1
*/

Class OB_Storage {
    
    // WARNING !
    // Reserved Variables, please don't override these public variables.
    // ( $lang, $log, $session, $security, $input, $benchmark ).
    
    public $properties   = array();
    public $profiler_var = array();  // profiler variable

    /**
    * Set Requested property
    * 
    * @param  string $key
    * @param  mixed  $val
    * @return void
    */
    public function __set($key, $val) 
    {
        $this->properties[$key] = $val;
    }
   
    // --------------------------------------------------------------------
   
    /**
    * Get Requested property
    * 
    * @param  string $property_name
    * @return mixed
    */
    public function __get($key) 
    {
        if(isset($this->properties[$key])) 
        {
            return($this->properties[$key]);
        } 
        else 
        {
            return(NULL);
        }
    } 
    
    // --------------------------------------------------------------------
    
    /**
    * Clone Storage objects for HMVC Requests, When we
    * use HMVC we use $this->storage = clone lib('ob/Storage');
    * that means we say to Storage class when Clone word used in HMVC library 
    * use cloned Storage object instead of orginals ( ersin ).
    */
    public function __clone()
    {
        foreach($this->properties as $name => $object)
        {
            if(is_object($object))
            {
                $this->properties[$name] = clone $object;
            }
        }
    }
    
}

// END Storage Class

/* End of file Storage.php */
/* Location: ./obullo/libraries/Storage.php */
