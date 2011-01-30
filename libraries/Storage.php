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
 * @copyright       Ersin Guvenc (c) 2009 - 2010.
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
* @author   Ersin Guvenc
*/

Class OB_Storage {
    
    // WARNING !
    // Reserved Variables, please don't override these public variables.
    // ( $view, $lang, $log, $session, $security, $input, $benchmark ).
    
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
    * Clone Empty objects for HMVC Requests, When we
    * use HMVC we use $this->empty = clone base_register('Storage');
    * that means we say to Empty class when Clone word used in HMVC library 
    * use cloned Empty objects instead of orginals ( ersin ).
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

/* End of file Empty.php */
/* Location: ./obullo/libraries/Storage.php */
