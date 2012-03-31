<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2012.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo     
 * @author          obullo.com
 * @copyright       Obullo Team
 * @since           Version 1.0
 * @filesource
 * @license
 */
 
 /**
 * Obullo Bootstrap file.
 * Control Your Application Boot
 * 
 * @package         Obullo 
 * @subpackage      Obullo.core
 * @category        Front Controller
 * @version         1.0
 */

//  Include application header files.
// -------------------------------------------------------------------- 
if( ! function_exists('ob_include_files'))
{
    function ob_include_files()
    {
        require (APP  .'config'. DS .'constants'. EXT);  // Your constants ..
        require (BASE .'config'. DS .'file_constants'. EXT);
        require (BASE .'core'. DS .'Registry'. EXT);
        require (BASE .'core'. DS .'Common'. EXT);
        require (BASE .'core'. DS .'Loader'. EXT);
        
        if(config_item('log_threshold') > 0) 
        { 
            require(BASE .'helpers'. DS .'core'. DS .'log'. EXT); 
        } 
    }
}

//  Include header functions. 
// -------------------------------------------------------------------- 
if( ! function_exists('ob_set_headers'))
{
    function ob_set_headers()
    {   
        if ( ! is_php('5.3')) // Kill magic quotes 
        {
            @set_magic_quotes_runtime(0); 
        }   
        
        ###  load core libraries ####
        
        lib('ob/URI');
        lib('ob/Router');
        lib('ob/Module'); // Parse module.xml file if its exist. 
        lib('ob/Lang');
        lib('ob/Benchmark');
        lib('ob/Input');
        
        ###  load core helpers ####

        loader::helper('core/error');
        loader::helper('core/input');
    }
}

//  Run the application.
// --------------------------------------------------------------------    
if( ! function_exists('ob_system_run'))
{
    function ob_system_run()
    {   
        $uri    = lib('ob/URI'); 
        $router = lib('ob/Router');
        
        benchmark_mark('total_execution_time_start');
        benchmark_mark('loading_time_base_classes_start');
        
        lib('ob/Input')->_sanitize_globals();  // Initalize to input filter. ( Sanitize must be above the GLOBALS !! )             
                                  
        $GLOBALS['d']   = $router->fetch_directory();   // Get requested directory
        $GLOBALS['s']   = $router->fetch_subfolder();   // Get subfolder if exists
        $GLOBALS['c']   = $router->fetch_class();       // Get requested controller
        $GLOBALS['m']   = $router->fetch_method();      // Get requested method

        $output = lib('ob/Output');
        $config = lib('ob/Config'); 
                
        if ($output->_display_cache($config, $uri) == TRUE) { exit; }  // Check REQUEST uri if there is a Cached file exist 
        
        $folder = 'controllers';
        
        if(defined('CMD'))  // Command Line Request
        {                
            if($GLOBALS['d'] != 'tasks')    // Check module and application folders.
            {                    
                if(is_dir(MODULES .$GLOBALS['sub_path'].$GLOBALS['d']. DS .'tasks'))
                {
                    $folder = 'tasks';
                } 
            }
        }
        
        if($GLOBALS['s'] != '')
        {
            $page_uri = "{$GLOBALS['d']} / {$GLOBALS['s']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            $controller = MODULES .$GLOBALS['sub_path'].$GLOBALS['d']. DS .$folder. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT;
            
            if(defined('CMD')) // call /app/tasks controller
            {
                if(file_exists(APP .'tasks'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT))
                {
                    $controller = APP .'tasks'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT;
                }
            }
            
            if ( ! file_exists($controller))  // Check the sub controller exists or not
            {
                if(config_item('enable_query_strings') === TRUE) show_404();
                
                show_404($page_uri);
            }
            
            $arg_slice  = 4;
            
            // Call the requested method.                1        2       3       4
            // Any URI segments present (besides the directory/subfolder/class/method) 
        } 
        else 
        {
            $page_uri = "{$GLOBALS['d']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            $controller = MODULES .$GLOBALS['sub_path'].$GLOBALS['d']. DS .$folder. DS .$GLOBALS['c']. EXT;
            
            if(defined('CMD'))  // call /app/tasks controller
            {
                if(file_exists(APP .'tasks'. DS .$GLOBALS['c']. EXT))
                {
                    $controller = APP .'tasks'. DS .$GLOBALS['c']. EXT;
                }
            }
            
            if ( ! file_exists($controller))   // Check the controller exists or not
            {
                if(config_item('enable_query_strings') === TRUE) show_404();
                
                throw new Exception('Unable to load your default controller.Please make sure the controller specified in your Routes.php file is valid.');
            }
            
            $arg_slice  = 3;
        }

        require (BASE .'core'. DS .'Controller'. EXT);  // We load Model File with a 'ob_autoload' function which is
                                                        // located in obullo/core/common.php.
                                                                   
        benchmark_mark('loading_time_base_classes_end');  // Set a mark point for benchmarking  
        benchmark_mark('execution_time_( '.$page_uri.' )_start');  // Mark a start point so we can benchmark the controller 
        
        require ($controller);  // call the controller.
        
        if ( ! class_exists($GLOBALS['c']) OR $GLOBALS['m'] == 'controller' 
              OR $GLOBALS['m'] == '_output'       // security fix.
              OR $GLOBALS['m'] == '_hmvc_output'
              OR $GLOBALS['m'] == '_instance'
              OR in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods('Controller')))
            )
        {
            show_404($page_uri);
        }
        
        $OB = new $GLOBALS['c']();           // If Everyting ok Declare Called Controller ! 

        if ( ! in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods($OB))))  // Check method exist or not 
        {
            show_404($page_uri);
        }
        
        $arguments = array_slice($OB->uri->rsegments, $arg_slice);
        
        if(defined('CMD'))  // Command Line Request Boolean Support
        {
            foreach($arguments as $k => $v)
            {                                           
                if($v == 'true')  { $arguments[$k] = TRUE; }
                if($v == 'false') { $arguments[$k] = FALSE; }
                if($v == 'null')  { $arguments[$k] = NULL; }
            }
        }
        
        //                                                                     0       1       2
        // Call the requested method. Any URI segments present (besides the directory/class/method) 
        // will be passed to the method for convenience
        call_user_func_array(array($OB, $GLOBALS['m']), $arguments);
        
        benchmark_mark('execution_time_( '.$page_uri.' )_end');  // Mark a benchmark end point 
        
        // Write Cache file if cache on ! and Send the final rendered output to the browser
        $output->_display();
            
    }
}

// Close the connections.
// --------------------------------------------------------------------  
if( ! function_exists('ob_system_close'))
{
    function ob_system_close()
    {
        $OB = this();
        
        foreach(loader::$_databases as $db_name => $db_var)  // Close all PDO connections..  
        {
            $OB->{$db_var} = NULL;
        }
        
        while (ob_get_level() > 0) // close all buffers.  
        { 
            ob_end_flush();    
        }        
    }
}


// END Bootstrap.php File

/* End of file Bootstrap.php
/* Location: ./obullo/core/Bootstrap.php */