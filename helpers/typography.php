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
 * Obullo Typography Helpers

 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */

// ------------------------------------------------------------------------

/**
* Convert newlines to HTML line breaks except within PRE tags
*
* @access	public
* @param	string
* @return	string
*/
if( ! function_exists('nl2br_except_pre') ) 
{
    function nl2br_except_pre($str)
    {
	    return base_register('Typography')->nl2br_except_pre($str);
    }
}
// ------------------------------------------------------------------------

/**
* Auto Typography Wrapper Function
*
*
* @access	public
* @param	string
* @param	bool	whether to reduce multiple instances of double newlines to two
* @return	string
*/
if( ! function_exists('auto_typography') ) 
{
    function auto_typography($str, $reduce_linebreaks = FALSE)
    {
        return base_register('Typography')->auto_typography($str, $reduce_linebreaks);
    }
}
    
/* End of file typography.php */
/* Location: ./obullo/helpers/typography.php */