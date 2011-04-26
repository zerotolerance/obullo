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
*/
if( ! function_exists('query') ) 
{
    function query()
    {
        
    }
}

/**
* INSERT
*/
if( ! function_exists('post') ) 
{
    function post()
    {
        
    }
}

/**
* UPDATE
*/
if( ! function_exists('put') ) 
{
    function put()
    {
        
    }
}


/**
* DELETE
*/
if( ! function_exists('delete') ) 
{
    function delete()
    {
        
    }
}

/**
* Object Query Function
*/
if( ! function_exists('ob_query') ) 
{
    function ob_query($type = 'GET', $query = '', $options = array())
    {
        $query = base_register('Query');
        
        $request_type = config_item('ob_query_request_type');
        
    }
}



  function api_process($type= '', $query, $sets= array())
  {
    $sets= (array)$sets;
    loader::lib('../api/process');
    $api= new process();
    $api->init();

    if(!empty($sets))
      foreach($sets AS $key => $value)
      {
        if($key == 'debug') $debug= TRUE;
        if($key == 'superuser' && API_REQUEST_TYPE == 'HMVC')
        {
          $api->set('key', this()->_generate_api_key());
        }
        $api->set($key, $value);
      }
    $api->run($type, $query);
    if($debug) $api->debug();
    return $api->response;
  }