<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2011.
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
 * Obullo JQuery Form Validation Plugin Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */


/**
* Json Send Error for Jquery Obullo
* Form Plugin
*
* @access public
* @param object | string $model or $system_message
* @return string
*/
if ( ! function_exists('form_json_error'))
{
    function form_json_error($model)
    {
        if(is_object($model))
        {
            if(isset($model->errors[$model->item('table')]['transaction_error']))
            {
                log_me('debug', 'TRANSACTION ERROR: '. $model->errors[$model->item('table')]['transaction_error']);

                return json_encode(array('success' => false, 'errors' => array('system_message' => 'We can\'t save data at this time please try again or contact us.')));
            }

            if(isset($model->errors[$model->item('table')]['redirect']))
            {
                return json_encode(array('success' => false, 'redirect' => $model->errors[$model->item('table')]['redirect']));
            }

            return json_encode(array('success' => false, 'errors' => $model->errors[$model->item('table')]));
        }
        else
        {
            return json_encode(array('success' => false, 'errors' => array('system_message' => $model)));
        }
    }
}
    
// ------------------------------------------------------------------------

/**
* Json send success, send succes if form data 
* save operation success.
*
* @access public
* @param string $message success msg
* @return string
*/
if ( ! function_exists('form_json_success'))
{
    function form_json_success($message)
    {
        return json_encode(array('success' => true, 'success_msg' => $message));   
    }
}

// ------------------------------------------------------------------------

/**
* Redirect user to second page using Jquery
* Obullo form plugin.
* 
* @param string $redirect_url /page/to/redirect
* @return string
*/
if ( ! function_exists('form_json_redirect'))
{
    function form_json_redirect($redirect_url)
    {
        return json_encode(array('success' => true, 'success_msg' => '', 'redirect' => $redirect_url));
    }
}

// ------------------------------------------------------------------------

/**
* Change form action dynamically and
* post data to another url if form validation success. 
* 
* @param string $post_url url you want to post 2nd page
* @return string
*/
if ( ! function_exists('form_json_forward'))
{
    function form_json_forward($post_url)
    {
        echo json_encode(array('success' => true, 'forward_url' => $post_url));
    }
}

/* End of file form_json.php */
/* Location: ./obullo/helpers/form_json.php */
