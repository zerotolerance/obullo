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
        $shutdown_errors = array(
        'ERROR'            => 'ERROR',            // E_ERROR 
        'PARSE ERROR'      => 'PARSE ERROR',      // E_PARSE
        'COMPILE ERROR'    => 'COMPILE ERROR',    // E_COMPILE_ERROR   
        'USER FATAL ERROR' => 'USER FATAL ERROR', // E_USER_ERROR
        );
        
        if(isset($shutdown_errors[$type]))  // We couldn't use object
        {
            $type = ucwords(strtolower($type));
            $err_level = config_item('error_reporting');
    
            if(defined('CMD'))  // If Command Line Request.
            {
                echo $type .': '. $e->getMessage(). ' File: ' .$e->getFile(). ' Line: '. $e->getLine(). "\n";
                
                log_me('error', 'Php Error Type (Cmd): '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE);
                
                return;
            }
    
            if($err_level > 0)  // If user want to display all errors
            {
                $sql = array();
                
                include(APP .'core'. DS .'errors'. DS .'ob_exception'. EXT);
            }
            else  // If display_errors = false, we show a blank page template.
            {
                include(APP .'core'. DS .'errors'. DS .'ob_disabled_error'. EXT);
            }
            
            log_me('error', 'Php Error Type: '.$type.'  --> '.$e->getMessage(). ' '.$e->getFile().' '.$e->getLine(), TRUE); 
             
        } 
        else
        {   
            $exception = base_register('Exception');
            
            if(is_object($exception)) 
            {            
                $exception->write($e, $type);
            }
        }
        
        return;
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
    
    if(defined('CMD'))  // If Command Line Request
    {
        return '['.$heading.']: The url ' .$message. ' you requested was not found.'."\n";
    }
    
    ob_start();
    include(APP. 'core'. DS .'errors'. DS .$template. EXT);
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
    if ($errno == 0) return;  
    
    switch ($errno)
    {
        case '1':       $type = 'ERROR'; break;             // E_ERROR
        case '2':       $type = 'WARNING'; break;           // E_WARNING
        case '4':       $type = 'PARSE ERROR'; break;       // E_PARSE
        case '8':       $type = 'NOTICE'; break;            // E_NOTICE
        case '16':      $type = 'CORE ERROR'; break;        // E_CORE_ERROR
        case '32':      $type = "CORE WARNING"; break;      // E_CORE_WARNING
        case '64':      $type = 'COMPILE ERROR'; break;     // E_COMPILE_ERROR
        case '128':     $type = 'COMPILE WARNING'; break;   // E_COMPILE_WARNING
        case '256':     $type = 'USER FATAL ERROR'; break;  // E_USER_ERROR
        case '512':     $type = 'USER WARNING'; break;      // E_USER_WARNING
        case '1024':    $type = 'USER NOTICE'; break;       // E_USER_NOTICE
        case '2048':    $type = 'STRICT ERROR'; break;      // E_STRICT
        case '4096':    $type = 'RECOVERABLE ERROR'; break; // E_RECOVERABLE_ERROR
        case '8192':    $type = 'DEPRECATED ERROR'; break;  // E_DEPRECATED
        case '16384':   $type = 'USER DEPRECATED ERROR'; break; // E_USER_DEPRECATED
        case '30719':   $type = 'ERROR'; break;             // E_ALL
    }
    
    Obullo_Exception_Handler(new ErrorException( $errstr, $errno, 0, $errfile, $errline), $type);   
    
    return;
}          

// -------------------------------------------------------------------- 

/**
* Catch last occured errors.
* 
* @return void
*/
function Obullo_Shutdown_Handler()
{                      
    $error = error_get_last();
                     
    if( ! $error) return;
    
    ob_get_level() AND ob_clean(); // Clean the output buffer

    $shutdown_errors = array(
    '1'   => 'ERROR',            // E_ERROR 
    '4'   => 'PARSE ERROR',      // E_PARSE
    '64'  => 'COMPILE ERROR',    // E_COMPILE_ERROR
    '256' => 'USER FATAL ERROR', // E_USER_ERROR
    );

    $type = (isset($shutdown_errors[$error['type']])) ? $shutdown_errors[$error['type']] : '';
    
    Obullo_Exception_Handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']), $type);
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
    elseif (strpos($file, MODULES) === 0)
    {
        $file = 'MODULES'. DS .substr($file, strlen(MODULES));
    }
    elseif (strpos($file, ROOT) === 0)
    {
        $file = 'ROOT'. DS .substr($file, strlen(ROOT));
    }

    return $file;  
}

// --------------------------------------------------------------------
 
/**
* Dump arguments
* This function borrowed from Kohana Php Framework.
* 
* @param  mixed $var
* @param  integer $length
* @param  integer $level
* @return mixed
*/
function error_dump_argument(& $var, $length = 128, $level = 0)
{
    if ($var === NULL)
    {
        return '<small>NULL</small>';
    }
    elseif (is_bool($var))
    {
        return '<small>bool</small> '.($var ? 'TRUE' : 'FALSE');
    }
    elseif (is_float($var))
    {
        return '<small>float</small> '.$var;
    }
    elseif (is_resource($var))
    {
        if (($type = get_resource_type($var)) === 'stream' AND $meta = stream_get_meta_data($var))
        {
            $meta = stream_get_meta_data($var);

            if (isset($meta['uri']))
            {
                $file = $meta['uri'];

                if (function_exists('stream_is_local'))
                {
                    if (stream_is_local($file))  // Only exists on PHP >= 5.2.4
                    {
                        $file = error_secure_path($file);
                    }
                }

                return '<small>resource</small><span>('.$type.')</span> '.htmlspecialchars($file, ENT_NOQUOTES, config_item('charset'));
            }
        }
        else
        {
            return '<small>resource</small><span>('.$type.')</span>';
        }
    }
    elseif (is_string($var))
    {
        // Encode the string
        $str = htmlspecialchars($var, ENT_NOQUOTES, config_item('charset'));
        
        return '<small>string</small><span>('.strlen($var).')</span> "'.$str.'"';
    }
    elseif (is_array($var))
    {
        $output = array();

        // Indentation for this variable
        $space = str_repeat($s = '    ', $level);

        static $marker;

        if ($marker === NULL)
        {
            // Make a unique marker
            $marker = uniqid("\x00");
        }

        if (empty($var))
        {
            // Do nothing
        }
        elseif (isset($var[$marker]))
        {
            $output[] = "(\n$space$s*RECURSION*\n$space)";
        }
        elseif ($level < 5)
        {
            $output[] = "<span>(";

            $var[$marker] = TRUE;
            foreach ($var as $key => & $val)
            {
                if ($key === $marker) continue;
                if ( ! is_int($key))
                {
                    $key = '"'.htmlspecialchars($key, ENT_NOQUOTES, config_item('charset')).'"';
                }

                $output[] = "$space$s$key => ".error_dump_argument($val, $length, $level + 1);
            }
            unset($var[$marker]);

            $output[] = "$space)</span>";
        }
        else
        {
            // Depth too great
            $output[] = "(\n$space$s...\n$space)";
        }

        return '<small>array</small><span>('.count($var).')</span> '.implode("\n", $output);
    }
    elseif (is_object($var))
    {
        ob_start();
        var_dump($var);
        $object_dump = ob_get_contents();
        ob_clean();
        
        $object_dump = str_replace("=>", '=><br />', $object_dump);
        $object_dump = str_replace("{", '{<br /><br />', $object_dump);
        
        return '<small>object '.$object_dump.'</small>';
        // .implode("\n", $output);
    }
    else
    {
        return '<small>'.gettype($var).'</small> '.htmlspecialchars(print_r($var, TRUE), ENT_NOQUOTES, config_item('charset'));
    }
    
}

// -------------------------------------------------------------------- 

/**
* Write File Source
* This function borrowed from Kohana Php Framework.
* 
* @author Ersin Guvenc
* @param  resource $file
* @param  mixed $line
* @param  mixed $padding
* 
* @return boolean | string
*/
function error_write_file_source($trace, $key = 0, $prefix = '')
{
    $debug = config_item('debug_backtrace'); 
    
    $file  = $trace['file'];
    $line_number = $trace['line'];
        
    if ( ! $file OR ! is_readable($file))
    {
        return FALSE;   // Continuing will cause errors
    }
    
    // Open the file and set the line position
    $file = fopen($file, 'r');
    $line = 0;

    // Set the reading range
    $range = array('start' => $line_number - $debug['padding'], 'end' => $line_number + $debug['padding']);
    
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

    $display = ($key > 0) ? ' class="collapsed" ' : '';
    
    return '<div id="error_toggle_'.$prefix.$key.'" '.$display.'><pre class="source"><code>'.$source.'</code></pre></div>';
    
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
                                           
if(config_item('error_reporting') != -1)  // Native error handler switch
{           
    set_error_handler('Obullo_Error_Handler'); 
    register_shutdown_function('Obullo_Shutdown_Handler');    // Enable the Obullo shutdown handler, which catches E_FATAL errors.
}  

set_exception_handler('Obullo_Exception_Handler');


// restore_error_handler();
// restore_exception_handler(); 

// END Errors.php File

/* End of file Errors.php */
/* Location: ./obullo/core/Errors.php */
