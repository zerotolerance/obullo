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

Class LogException extends CommonException {}  

/**
 * Logging Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */
if( ! isset($_ob->log)) 
{
    $_ob = base_register('Empty');
    $_ob->log = new stdClass();

    $_ob->log->_log_path  = '';
    $_ob->log->_threshold = 1;
    $_ob->log->_date_fmt  = 'Y-m-d H:i:s';
    $_ob->log->_enabled   = TRUE;
    $_ob->log->_levels    = array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');


    $_config = get_config();
    $_ob->log->_log_path = ($_config['log_path'] != '') ? $_config['log_path'] : APP.'system'.DS.'logs'.DS;

    if ( ! is_dir($_ob->log->_log_path) OR ! is_really_writable($_ob->log->_log_path))
    {
        $_ob->log->_enabled = FALSE;
    }

    if (is_numeric($_config['log_threshold']))
    {
        $_ob->log->_threshold = $_config['log_threshold'];
    }
        
    if ($_config['log_date_format'] != '')
    {
        $_ob->log->_date_fmt = $_config['log_date_format'];
    }
}

// --------------------------------------------------------------------

/**
 * Write Log File
 *
 * Generally this function will be called using the global log_me() function
 *
 * @access   public
 * @param    string    the error level
 * @param    string    the error message
 * @param    bool    whether the error is a native PHP error
 * @return   bool
 */        
if( ! function_exists('log_write') ) 
{
    function log_write($level = 'error', $msg, $php_error = FALSE)
    {        
        $_ob = base_register('Empty');
        
        if ($_ob->log->_enabled === FALSE)
        {
            return FALSE;
        }

        $level = strtoupper($level);
        
        if ( ! isset($_ob->log->_levels[$level]) OR ($_ob->log->_levels[$level] > $_ob->log->_threshold))
        {
            return FALSE;
        }

        $filepath = $_ob->log->_log_path.'log-'.date('Y-m-d').EXT;
        $message  = '';
        
        if ( ! file_exists($filepath))
        {
            $message .= "<"."?php  if ( ! defined('BASE')) exit('Access Denied!'); ?".">\n\n";
        }
            
        if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
        {
            return FALSE;
        }

        $message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($_ob->log->_date_fmt). ' --> '.$msg."\n";
        
        flock($fp, LOCK_EX);    
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);         
        return TRUE;
    }
}

/* End of file log.php */
/* Location: ./obullo/helpers/loaded/log.php */