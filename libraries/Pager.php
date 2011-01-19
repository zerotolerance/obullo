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
 * Obullo Pager Class
 *
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Libraries
 * @author        Ersin Guvenc
 * @author        Derived from PEAR pager package.
 * @see           Original package http://pear.php.net/package/Pager
 * @link          
 */
Class OB_Pager
{   
    /**
    * Return a pager based on $mode and $options
    *
    * @param array $options Optional parameters for the storage class
    *
    * @return object Storage object
    * @static
    * @access public
    */
    public function __construct($options = array())
    {
        $mode = (isset($options['mode']) ? strtolower($options['mode']) : 'jumping');
        
        require_once 'OB_Pager_common.php';
        
        $classname = 'Pager_'.$mode;
        $classfile = 'drivers'. DS .'pager'. DS .'OB_Pager_'. $mode. EXT;

        if ( ! class_exists($classname)) 
        {
            include_once $classfile;
        }

        // If the class exists, return a new instance of it.
        if (class_exists($classname)) 
        {
            $pager = new $classname($options);
            return $pager;
        }

        return NULL;
    }

}

// END Pager Class

/* End of file Pager.php */
/* Location: ./obullo/libraries/Pager.php */