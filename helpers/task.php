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
 * @updated by         CJ Lazell
 * @license         public
 * @since           Version 1.0
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Obullo Task Helpers
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @link
 */

/**
* Run Command Line Tasks
*
* @param  array $uri
* @return void
*/
if ( ! function_exists('task_run'))
{
  function task_run($uri)
  {
      $uri = explode('/', $uri);

      $module= array_shift($uri);

      foreach($uri AS $i => $section)
        if(!$section) $uri[$i]= 'false';

      $shell = PHP_PATH.' '.FPATH.'/task.php '.$module.' '.implode('/', $uri);

      exec(escapeshellcmd($shell) .' > /dev/null &');
  }
}


/* End of file task.php */
/* Location: ./obullo/helpers/task.php */
