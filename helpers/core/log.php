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

/**
 * Logging Helper
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */

// --------------------------------------------------------------------

/**
 * Write Log File
 *
 * Generally this function will be called using the global log_me() function.
 * Do not use Object in this function.
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
        $_log_path  = '';
        $_threshold = 1;
        $_date_fmt  = 'Y-m-d H:i:s';
        $_enabled   = TRUE;
        $_levels    = array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

        $_config    = get_config();
        $_log_path  = ($_config['log_path'] != '') ? $_config['log_path'] : APP .'system'. DS .'logs'. DS;

        if ( ! is_dir($_log_path) OR ! is_really_writable($_log_path))
        {
            $_enabled = FALSE;
        }

        if (is_numeric($_config['log_threshold']))
        {
            $_threshold = $_config['log_threshold'];
        }
            
        if ($_config['log_date_format'] != '')
        {
            $_date_fmt = $_config['log_date_format'];
        }
        
        if ($_enabled === FALSE)
        {
            return FALSE;
        }

        $level = strtoupper($level);
        
        if ( ! isset($_levels[$level]) OR ($_levels[$level] > $_threshold))
        {
            return FALSE;
        }

        $filepath = $_log_path.'log-'.date('Y-m-d').EXT;
        $message  = '';
        
        if ( ! file_exists($filepath))
        {
            $message .= "<"."?php  if ( ! defined('BASE')) exit('Access Denied!'); ?".">\n\n";
        }
            
        if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
        {
            return FALSE;
        }

        $message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($_date_fmt). ' --> '.$msg."\n";
        
        flock($fp, LOCK_EX);    
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);

        @chmod($filepath, FILE_WRITE_MODE);         
        return TRUE;
    }
}

/* End of file log.php */
/* Location: ./obullo/helpers/core/log.php */