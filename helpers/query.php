<?php
defined('BASE') or exit('Access Denied!'); 

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @license         public
 * @since           Version 1.0
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Obullo Date Helpers
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @link        
 */

// ------------------------------------------------------------------------

/**
* GET
* Do GET query throught to OB QUERY models.
* 
* @param string $query Query SQL
* @param array  $options
*/
if( ! function_exists('query') ) 
{
    function query($query, $options = array())
    {
        return ob_query('GET', $query, $options);
    }
}

// ------------------------------------------------------------------------

/**
* INSERT
*/
if( ! function_exists('post') ) 
{
    function post($table, $data, $options = array())
    {
        return ob_query('POST', array($table => $data), $options);
    }
}

// ------------------------------------------------------------------------

/**
* UPDATE
*/
if( ! function_exists('put') ) 
{
    function put($table, $data, $options = array())
    {
        return ob_query('PUT', array($table => $data), $options);
    }
}

// ------------------------------------------------------------------------

/**
* DELETE
*/
if( ! function_exists('delete') ) 
{
    function delete($table, $data, $options = array())
    {
        return ob_query('DELETE', array($table => $data), $options);
    }
}

// ------------------------------------------------------------------------

/**
* Main Object Query Function
* 
* @param string $type
* @param string $query
* @param array  $options
* 
* @return mixed
*/
if( ! function_exists('ob_query') ) 
{
    function ob_query($type = 'GET', $query = '', $options = array())
    {
        $query = base_register('Query');
        
        $request_type = config_item('ob_query_request_type');
        
        $debug = FALSE;
        if(count($options) > 0)
        {
            foreach($options as $key => $val)
            {
                if($key == 'debug')
                {
                    $debug = TRUE;
                }
                
                $query->set_var($key, $val);
            }
        }
        
        $query->exec($type, $query);
        
        if($debug)
        {
            $query->debug();
        }
        
        return $query->response('json', true);
    }
}

/* End of file query.php */
/* Location: ./obullo/helpers/query.php */