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
 * @since           Version 1.0
 * @filesource
 * @license
 */  

 
/**
* Catch Exceptions
* 
* @param object $e
*/
if( ! function_exists('Obullo_Exception_Handler')) 
{
    function Obullo_Exception_Handler($e, $type = '')
    {   
        if($type == 'PARSE ERROR')  // We couldn't use object
        {
            $type = ucwords(strtolower($type));
            
            ob_start();
            include(ROOT . APP .'core'. DS .'errors'. DS .'ob_exception'. EXT);
            $buffer = ob_get_clean(); 

            echo $buffer;
            
            log_me('error', 'Php Error Type: '.$type.'  --> '.$errstr. ' '.$errfile.' '.$errline, TRUE);
        } 
        else
        {   
            $Exception = base_register('Exception');
            
            if(is_object($Exception)) 
            {
                echo $Exception->write_exception($e, $type);
            }
        }
    }    
}   

// -------------------------------------------------------------------- 
 
/**
* 404 Page Not Found Handler
*
* @access   private
* @param    string
* @return   string
*/
function show_404($page = '')
{   
    log_me('error', '404 Page Not Found --> '.$page);
    echo show_http_error('404 Page Not Found', $page, 'ob_404', 404);

    exit;
}

// -------------------------------------------------------------------- 

/**
* Manually Set General Http Errors
* 
* @param string $message
* @param int    $status_code
* @param int    $heading
* 
* @version 0.1
* @version 0.2  added custom $heading params for users
*/
function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
{
    log_me('error', 'HTTP Error --> '.$message); 
    echo show_http_error($heading, $message, 'ob_general', $status_code);
    
    exit;
}
                   
// --------------------------------------------------------------------

/**
 * General Http Errors
 *
 * @access   private
 * @param    string    the heading
 * @param    string    the message
 * @param    string    the template name
 * @param    int       header status code
 * @return   string
 */
function show_http_error($heading, $message, $template = 'ob_general', $status_code = 500)
{
    set_status_header($status_code);

    $message = implode('<br />', ( ! is_array($message)) ? array($message) : $message);
    
    ob_start();
    include(ROOT . APP. 'core'. DS .'errors'. DS .$template. EXT);
    $buffer = ob_get_clean();
    
    return $buffer;
}

// --------------------------------------------------------------------

/**
* Main Error Handler
* Predefined error constants
* http://usphp.com/manual/en/errorfunc.constants.php
* 
* 1     E_ERROR
* 2     E_WARNING
* 4     E_PARSE
* 8     E_NOTICE
* 16    E_CORE_ERROR
* 32    E_CORE_WARNING
* 64    E_COMPILE_ERROR
* 128   E_COMPILE_WARNING
* 256   E_USER_ERROR
* 512   E_USER_WARNING
* 1024  E_USER_NOTICE
* 2048  E_STRICT
* 4096  E_RECOVERABLE_ERROR
* 8192  E_DEPRECATED
* 16384 E_USER_DEPRECATED
* 30719 E_ALL
* 
* @param int $errno
* @param string $errstr
* @param string $errfile
* @param int $errline
*/
function Obullo_Error_Handler($errno, $errstr, $errfile, $errline)
{
    if (($errno AND error_reporting()) == 0) return;
    
    switch ($errno)
    {
        case '1':       $type = 'ERROR'; break;
        case '2':       $type = 'WARNING'; break;
        case '4':       $type = 'PARSE ERROR'; break;
        case '8':       $type = 'NOTICE'; break;
        case '16':      $type = 'CORE ERROR'; break;
        case '32':      $type = "CORE WARNING"; break;
        case '64':      $type = 'COMPILE ERROR'; break;
        case '128':     $type = 'COMPILE WARNING'; break;
        case '256':     $type = 'USER FATAL ERROR'; break;
        case '512':     $type = 'USER WARNING'; break;
        case '1024':    $type = 'USER NOTICE'; break;
        case '2048':    $type = 'STRICT ERROR'; break;
        case '4096':    $type = 'RECOVERABLE ERROR'; break;
        case '8192':    $type = 'DEPRECATED ERROR'; break;
        case '16384':   $type = 'USER DEPRECATED ERROR'; break;
        case '30719':   $type = 'ERROR'; break;
    }
    
    Obullo_Exception_Handler(new ErrorException( $errstr, $errno, 0, $errfile, $errline), $type);
    
    return;
}          

// -------------------------------------------------------------------- 

function Obullo_Shutdown_Handler()
{                      
    $error = error_get_last();
    
    if( ! $error) return;
    
    ob_get_level() AND ob_clean(); // Clean the output buffer

    Obullo_Error_Handler($error['type'], $error['message'], $error['file'], $error['line']);
    
    exit();
}

// --------------------------------------------------------------------  

/**
* Don't show root paths for security
* reason.
* 
* @param  string $file
* @return 
*/
function error_secure_path($file)
{
    if (strpos($file, APP) === 0)
    {
        $file = 'APP'. DS . substr($file, strlen(APP));
    }
    elseif (strpos($file, BASE) === 0)
    {
        $file = 'BASE'. DS .substr($file, strlen(BASE));
    }
    elseif (strpos($file, DIR) === 0)
    {
        $file = 'DIR'. DS .substr($file, strlen(DIR));
    }
    elseif (strpos($file, ROOT) === 0)
    {
        $file = 'ROOT'. DS .substr($file, strlen(ROOT));
    }

    return $file;  
}

// -------------------------------------------------------------------- 

/**
* This function borrowed from Kohana Php Framework.
* 
* @author Ersin Guvenc
* @param  resource $file
* @param  mixed $line
* @param  mixed $padding
* 
* @return boolean | string
*/
function error_write_file_source($file, $line_number, $id = '0', $padding = 5)
{
    if ( ! $file OR ! is_readable($file))
    {
        return FALSE;   // Continuing will cause errors
    }

    // Open the file and set the line position
    $file = fopen($file, 'r');
    $line = 0;

    // Set the reading range
    $range = array('start' => $line_number - $padding, 'end' => $line_number + $padding);
    
    $format = '% '.strlen($range['end']).'d';    // Set the zero-padding amount for line numbers

    $source = '';
    while (($row = fgets($file)) !== FALSE)
    {
        if (++$line > $range['end'])  // Increment the line number
            break;

        if ($line >= $range['start'])
        {
            $row = htmlspecialchars($row, ENT_NOQUOTES, config_item('charset'));  // Make the row safe for output

            $row = '<span class="number">'.sprintf($format, $line).'</span> '.$row;  // Trim whitespace and sanitize the row

            if ($line === $line_number)
            {
                $row = '<span class="line highlight">'.$row.'</span>';  // Apply highlighting to this row
            }
            else
            {
                $row = '<span class="line">'.$row.'</span>';
            }
            
            $source .= $row;  // Add to the captured source
        }
    }
    
    fclose($file);  // Close the file

    $display = ($id > 0) ? ' style="display:none;" ' : '';
    
    return '<span id="error_toggle_'.$id.'" '.$display.'><pre class="source"><code>'.$source.'</code></pre></span>';
}

// -------------------------------------------------------------------- 

/**
* Debug Backtrace
* 
* @param mixed $e
*/
function error_debug_backtrace($e)
{
    $trace = $e->getTrace();      // Get the exception backtrace

    if ($e instanceof ErrorException)
    {
        if (version_compare(PHP_VERSION, '5.3', '<'))
        {
            // Workaround for a bug in ErrorException::getTrace() that exists in
            // all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
            for ($i = count($trace) - 1; $i > 0; --$i)
            {
                if (isset($trace[$i - 1]['args']))
                {
                    $trace[$i]['args'] = $trace[$i - 1]['args'];  // Re-position the args

                    unset($trace[$i - 1]['args']); // Remove the args
                }
            }
        }
    }
    
    return $trace;
}

// error_reporting(0);     // we need to close error reporting we already catch the fatal errors. 
set_error_handler('Obullo_Error_Handler'); 
set_exception_handler('Obullo_Exception_Handler');
register_shutdown_function('Obullo_Shutdown_Handler');    // Enable the Obullo shutdown handler, which catches E_FATAL errors.  


// END Errors.php File

/* End of file Errors.php */
/* Location: ./obullo/core/Errors.php */
