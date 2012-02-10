<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009 - 2012.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         Obullo
 * @author          Obullo.com
 * @subpackage      Obullo.core
 * @copyright       Obullo Team
 * @license
 * @filesource
 */

Class LoaderException extends CommonException {}

// ------------------------------------------------------------------------

/**
 * Loader Class (Obullo Loader)
 * Load Obullo library, model, config, lang and any other files ...
 */

Class OB_Loader {

    /**
    * Track "local" helper files.
    * @var array
    */
    public static $_helpers      = array();

    /**
    * Track "base" helper files.
    * @var array
    */
    public static $_base_helpers = array();
    
    /**
    * Track "overriden" helpers.
    *
    * @var array
    */
    public static $_overriden_helpers = array();
    
    /**
    * Track "application" helper files.
    * @var array
    */
    public static $_app_helpers  = array();
      
    /**
    * Track db names.
    * @var array
    */
    public static $_databases    = array();
    
    /**
    * Track model names.
    * @var array
    */
    public static $_models       = array();

    /**
    * loader::lib();
    *
    * load libraries from /module folder. (current module) 
    *
    * @param    mixed $class
    * @param    mixed $params_or_no_instance array | null | false 
    * @param    string | boolean $object_name_or_new_instance
    * @return   self::_library()
    */
    public static function lib($class = '', $params_or_no_instance = '', $object_name_or_new_instance = '')
    {
        if($class == '')
        {
            return FALSE;
        }
        
        if(is_bool($object_name_or_new_instance))
        {
            $object_name  = '';
            $new_instance = $object_name_or_new_instance;
        }
        else 
        {
            $new_instance = FALSE;
            $object_name  = $object_name_or_new_instance;
        }
        
        // Obullo Libraries
        // --------------------------------------------------------------------
        
        if(strpos($class, 'ob/') === 0)
        {
            $library = strtolower(substr($class, 3));
            
            if($params_or_no_instance === FALSE)
            {   
                return lib($class, $params_or_no_instance, $new_instance);
            } 
            
            // If someone use HMVC we need to create new instance() foreach Library
            if(lib('ob/Router')->hmvc == FALSE)
            {
                if (isset(this()->{$library}) AND is_object(this()->{$library}))
                {
                    return;
                }
            }

            this()->{$library} = lib($class, $params_or_no_instance, $new_instance);

            profiler_set('ob_libraries', $library, $library);
            
            return;
        }

        // Application Libraries
        // --------------------------------------------------------------------
        
        if(strpos($class, 'app/') === 0)
        {
            return self::_library(substr($class, 4), $params_or_no_instance, $object_name, $new_instance, $app_library = TRUE);
        }
        
        self::_library($class, $params_or_no_instance, $object_name, $new_instance);
    }

    // --------------------------------------------------------------------
                             
    /**
    * Obullo Library Loader.
    *
    * @author   Ersin Guvenc
    * @param    string $class class name
    * @param    array | boolean $params_or_no_ins __construct() params  | or | No Instantiate
    * @param    boolean $new_instance create new instance
    *
    * @return   void
    */
    protected static function _library($class, $params_or_no_ins = '', $object_name = '', $new_instance = FALSE, $app_folder = FALSE)
    {
        $OB = this();

        $case_sensitive = ($params_or_no_ins == FALSE) ? TRUE : FALSE;
        
        $data = self::_load_file($class, $folder = 'libraries', $app_folder, $case_sensitive);

        $class_var = '';

        if( file_exists($data['file']))
        {                    
            require_once($data['file']);

            $class_var = strtolower($data['file_name']);

            if($object_name != '') 
            {
                $class_var = $object_name;
            }
            
            if(is_array($params_or_no_ins))  // HMVC need to create new instance() foreach Library
            {
                if(lib('ob/Router')->hmvc == FALSE AND $new_instance == FALSE)
                {
                    if (isset($OB->$class_var) AND is_object($OB->$class_var))
                    {
                        return;
                    }
                }
                
                if(class_exists($data['file_name']))
                {
                    $OB->$class_var = new $data['file_name']($params_or_no_ins);
                }

                profiler_set('libraries', $class_var, $class_var);

                self::_assign_core_libraries($class_var);
                
                return;
            }
            elseif($params_or_no_ins === FALSE)
            {
                profiler_set('libraries', $class_var.'_no_instantiate', $class_var);

                return;
            }
            else
            {
                if (isset($OB->$class_var) AND is_object($OB->$class_var))
                {
                    return;
                }
                
                if(class_exists($data['file_name']))
                {
                    $Class = ucfirst($data['file_name']);
                    $OB->$class_var = new $Class();
                }
                
                profiler_set('libraries', $class_var, $class_var);

                self::_assign_core_libraries($class_var);
               
                return;
            }
        }
        
        throw new LoaderException('Unable to locate the library file: '. $data['file']);
    }

    // --------------------------------------------------------------------

    /**
    * loader::model();
    * loader::model('subfolder/model_name')  sub folder load
    * loader::model('../module/model_name')  model load from outside directory
    * loader::model('modelname', FALSE); no instantite just include class.
    * 
    * @author    Ersin Guvenc
    * @param     string $model
    * @param     string $object_name_OR_NO_INS
    * @param     array | boolean $params (construct params) | or | Not Instantiate just include file
    * @return    void
    */
    public static function model($model, $object_name_or_no_ins = '', $params_or_no_ins = '')
    {
        $new_instance = FALSE;
        
        if($object_name_or_no_ins === TRUE)
        {
            $new_instance = TRUE;
            $object_name_or_no_ins = '';
        }
        
        if(strpos($model, 'app/') === 0) // Application Model
        {
            $data = self::_load_file(substr($model, 4), $folder = 'models', $app_folder = TRUE);

            self::_model($data['file'], $data['file_name'], $object_name_or_no_ins, $params_or_no_ins, $new_instance);
        }
        
        $case_sensitive = ($object_name_or_no_ins === FALSE || $params_or_no_ins === FALSE) ? $case_sensitive = TRUE : FALSE;
        
        $data = self::_load_file($model, $folder = 'models', FALSE , $case_sensitive);

        self::_model($data['file'], $data['file_name'], $object_name_or_no_ins, $params_or_no_ins, $new_instance);
    }

    // --------------------------------------------------------------------

    /**
    * Load _model
    *
    * @access    private
    * @param     string $file
    * @param     string $model_name
    * @param     string $object_name
    * @param     array | boolean  $params_or_no_ins
    * @param     boolean $new_instance  create new instance
    * @version   0.1
    * @version   0.2 added params_or_no_ins instantiate switch ,added Ssc::instance()->_profiler_mods
    * @version   0.3 added profiler_set function
    * @version   0.4 HMVC bug fixed.
    */
    protected static function _model($file, $model_name, $object_name = '', $params_or_no_ins = '', $new_instance = FALSE)
    {
        if ( ! file_exists($file))
        {
            throw new LoaderException('Unable to locate the model: '.$file);
        }

        $model_var = $model_name;
        
        if($object_name != '' OR $object_name != NULL)
        {
            $model_var = $object_name;
        }

        $OB = this();

        // If someone use HMVC we need to create new instance() foreach HMVC requests.
        if(lib('ob/Router')->hmvc == FALSE AND $new_instance == FALSE)
        {
            if (isset($OB->$model_var) AND is_object($OB->$model_var))
            {
                return;   
            }
        }
        
        #####################

        require_once($file);
        
        #####################
        
        $model = ucfirst($model_name);

        if($params_or_no_ins === FALSE || $object_name === FALSE)
        {
            profiler_set('models', $model_var.'_no_instantiate', $model_name);
            
            return;
        }

        if( ! class_exists($model, false)) // autoload false.
        {
            throw new LoaderException('You have a small problem, model name isn\'t right in here: '.$model);
        }

        loader::$_models[$model_var] = $model_var;
        
        profiler_set('models', $model_var, $model_var);     // should be above the new model();

        $OB->$model_var = new $model($params_or_no_ins);    // register($class); we don't need it

        // assign all loaded db objects inside to current model
        // loader::database() support for Model_x { function __construct() { loader::database() }}
        $OB->$model_var->_assign_db_objects();
    }

    // --------------------------------------------------------------------

    /**
    * loader::database();
    *
    * Database load.
    * This function loads the database for controllers
    * and model files.
    *
    * @author   Ersin Guvenc
    * @param    mixed $db_name for manual connection
    * @param    boolean $return_object return to db object switch
    * @param    boolean $use_active_record active record switch
    * @return   void
    */
    public static function database($db_name = 'db', $return_object = TRUE, $use_active_record = TRUE)
    {
        $OB = this();

        $db_var = (empty($db_name)) ? 'db' : $db_name;

        if(is_array($db_name) AND isset($db_name['variable']))
        {
            $db_var = $db_name['variable'];
        }

        if (isset($OB->{$db_var}) AND is_object($OB->{$db_var}))
        {
            if($return_object)
            {
                return $OB->{$db_var};
            }

            return;
        }

        if($return_object === FALSE)
        {
            profiler_set('databases', $db_name, $db_var);  // Store db variables ..

            return OB_DBFactory::Connect($db_name, $db_var, $use_active_record); // Return to database object ..
        }

        $OB->{$db_var} = OB_DBFactory::Connect($db_name, $db_var, $use_active_record);   // Connect to Database

        loader::$_databases[$db_name] = $db_var;
        
        profiler_set('databases', $db_name, $db_var);  // Store db variables

        self::_assign_db_objects($db_var);
        
    }

    // --------------------------------------------------------------------

    /**
    * loader::helper();
    *
    * loader::helper('subfolder/helper_name')  local sub folder load
    * loader::helper('../outside_folder/helper_name')  outside directory load
    *
    * We have three helper directories
    *   o Obullo/helpers: ob/ helpers
    *   o App/helpers   : app/ helpers
    *   o Local/helpers : module helpers
    *
    * @author   Ersin Guvenc
    * @param    string $helper
    * @return   void
    */
    public static function helper($helper)
    {
        // Obullo Helpers
        // --------------------------------------------------------------------
        
        if(strpos($helper, 'ob/') === 0)
        {
            return loader::_helper(substr($helper, 3));
        }
                
        // Application Helpers
        // --------------------------------------------------------------------
        
        if(strpos($helper, 'app/') === 0) // Application Helpers
        {
            $helper = substr($helper, 4);
            
            if( isset(self::$_app_helpers[$helper]) )
            {
                return;
            }

            $data = self::_load_file($helper, $folder = 'helpers', $app_folder = TRUE);

            if(file_exists($data['file']))
            {
                include($data['file']);

                self::$_app_helpers[$helper] = $helper;

                return;
            }

            throw new LoaderException('Unable to locate the application helper: ' .$data['file']);
        }
                 
        // Core helpers
        // --------------------------------------------------------------------
        
        if(strpos($helper, 'core/') === 0) // Obullo Core Helpers
        {
            return loader::_helper(substr($helper, 5), true);
        }
       
        // Module Helpers
        // --------------------------------------------------------------------
        
        if( isset(self::$_helpers[$helper]) )
        {
            return;
        }
        
        $data = self::_load_file($helper, $folder = 'helpers');

        if(file_exists($data['file']))
        {
            include($data['file']);

            self::$_helpers[$helper] = $helper;

            return;
        }

        throw new LoaderException('Unable to locate the helper: '.$data['file']);
    }

    // --------------------------------------------------------------------

    /**
    * Private helper loader.
    *
    * @author   Ersin Guvenc
    * @param    string $helper
    * @return   void
    */
    protected static function _helper($helper, $core = FALSE)
    {            
        if( isset(self::$_base_helpers[$helper]) )
        {
            return;
        }

        $core_path = ($core) ? 'core'. DS : '';
               
        if(file_exists(BASE .'helpers'. DS . $core_path . $helper. EXT))
        {
            $prefix      = config_item('subhelper_prefix');
            $module      = lib('ob/Router')->fetch_directory();
            $sub_module  = lib('ob/URI')->fetch_sub_module();
            
            $module_path = $GLOBALS['sub_path'].$module;
            
            if( ! isset(self::$_overriden_helpers[$helper]))
            {
                $module_xml = lib('ob/Module'); // parse module.xml 

                if($module_xml->xml() != FALSE)
                {
                    $extensions = $module_xml->get_extensions();

                    if(count($extensions) > 0)   // Parse Extensions
                    {
                        foreach($extensions as $ext_name => $extension)
                        { 
                            $attr = $extension['attributes'];

                            if($attr['enabled'] == 'yes')
                            {
                                if(isset($extension['override']['helpers']))
                                {
                                    foreach($extension['override']['helpers'] as $helper_item)
                                    {               
                                        if( ! isset(self::$_overriden_helpers[$helper_item]))  // Singleton
                                        {
                                            if($helper == $helper_item) // Do file_exist for defined helper.
                                            {    
                                                if(file_exists($attr['root'] .$ext_name. DS .'helpers'. DS .$prefix. $helper. EXT))  
                                                {
                                                    $helpername = $prefix. $helper;
                                                    
                                                    include($attr['root'] .$ext_name. DS .'helpers' . DS .$prefix. 'error'. EXT); 
                                                    
                                                    profiler_set('helpers', 'php_'. $helper . '_overridden', $prefix . $helpername);

                                                    self::$_overriden_helpers[$helper_item] = $helper_item;
                                                } 
                                            } 
                                        }
                                    }   
                                }
                            }
                        }
                    }
                }
            }  
     
            //------ end extensions override support -----//
            
            if( ! isset(self::$_overriden_helpers[$helper]))
            {
                if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'helpers'. DS .$prefix. $helper. EXT)) // If module my_helper exist..
                {
                    include(MODULES .$GLOBALS['sub_path'].$module. DS .'helpers'. DS .$prefix. $helper. EXT);

                    self::$_base_helpers[$prefix . $helper] = $prefix . $helper;

                    self::$_overriden_helpers[$helper] = $helper;
                }
                elseif(file_exists(APP .'helpers'. DS .$prefix. $helper. EXT))  // If application my_helper exist.
                {
                    include(APP .'helpers'. DS .$prefix. $helper. EXT);

                    self::$_base_helpers[$prefix . $helper] = $prefix . $helper;

                    self::$_overriden_helpers[$helper] = $helper;
                }
            }
            
            include(BASE .'helpers'. DS .$core_path . $helper. EXT);

            self::$_base_helpers[$helper] = $helper;

            return;
        }

        $type = ($core) ? 'core' : 'base';
        
        throw new LoaderException('Unable to locate the '.$type.' helper: ' .$helper. EXT);
        
    }

    // --------------------------------------------------------------------

    /**
    * Load language files.
    * 
    * @param string $file
    * @param string $folder
    * @param bool $return 
    */
    public static function lang($file, $folder = '', $return = FALSE)
    {
        lib('ob/Lang')->load($file, $folder, NULL, $return);
    }

    // --------------------------------------------------------------------
    
    /**
    * Load config files.
    * 
    * @param string $file
    * @param bool $use_sections
    * @param bool $fail_gracefully 
    */
    public static function config($file, $use_sections = FALSE, $fail_gracefully = FALSE)
    {
        lib('ob/Config')->load($file, $use_sections, $fail_gracefully);
    }
 
    // --------------------------------------------------------------------

    /**
    * Common file loader for models and
    * helpers functions.
    *
    * @author  Ersin Guvenc
    *
    * @param string $filename
    * @param string $folder
    * @param string $loader_func
    *
    * return array  file_name | file
    */
    protected static function _load_file($filename, $folder = 'helpers', $app_folder = FALSE, $case_sensitive = FALSE)
    {
        $sub_module_path = $GLOBALS['sub_path'];
        
        if( ! is_string($filename))
        {
            throw new LoaderException('Loader function filenames must be string.');
        }
        
        $real_name  = ($case_sensitive) ? trim($filename, '/') : strtolower(trim($filename, '/'));
        $root       = rtrim(MODULES. $sub_module_path, DS); 
        
        $sub_root   = $GLOBALS['d']. DS .$folder. DS;
        if($app_folder)
        {
            $root     = APP . $folder;
            $sub_root = '';
        }
        
        if(strpos($real_name, '../sub.') === 0)   // sub.module/module folder request
        {
            $paths          = explode('/', substr($real_name, 3)); 
            $filename       = array_pop($paths);           // get file name
            $sub_modulename = array_shift($paths);     // get sub module name
            $modulename     = array_shift($paths);     // get module name
            
            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .public/css/sub/welcome.css  sub dir support
            }
            
            $file = MODULES .$sub_modulename. DS .SUB_MODULES. $modulename. DS .$folder . DS . $sub_path . $filename. EXT;
            
            $return['file_name'] = $filename;
            $return['file']      = $file;

            return $return;
        }

        if(strpos($real_name, '../') === 0)   // ../module folder request
        {
            $sub_module_path = ''; // clear sub module path
            
            $paths      = explode('/', substr($real_name, 3));
            $filename   = array_pop($paths);         // get file name
            $modulename = array_shift($paths);       // get module name

            $sub_path   = '';
            if( count($paths) > 0)
            {
                $sub_path = implode(DS, $paths) . DS;      // .public/css/sub/welcome.css  sub dir support
            }
            
            //---------- Extension Support -----------//
            
            if(extension('enabled', $modulename) == 'yes') // If its a enabled extension
            {
                if(strpos(extension('path', $modulename), 'sub.') === 0) // If extension working path is a sub.module.
                {
                    $file_url = '../'.extension('path', $modulename).'/'.$modulename.'/'.$filename;

                    if($sub_path != '')
                    {
                        $file_url = '../'.extension('path', $modulename).'/'.$modulename.'/'.str_replace(DS, '/', $sub_path).'/'.$filename;
                    }
     
                    return self::load_file($file_url);
                }
            }
            
            //---------- Extension Support -----------//
            
            $file = MODULES . $sub_module_path.$modulename . DS . $folder . DS . $sub_path . $filename. EXT;
            
            $return['file_name'] = $filename;
            $return['file']      = $file;

            return $return;
        }

        if(strpos($real_name, '/') > 0)         //  Sub folder request
        {
            $paths      = explode('/',$real_name);   // paths[0] = path , [1] file name
            $filename   = array_pop($paths);          // get file name
            $path       = implode(DS, $paths);

            $return['file_name'] = $filename;
            $return['file']      = $root. DS .$sub_root. $path. DS .$filename. EXT;

            return $return;
        }


        return array('file_name' => $real_name, 'file' => $root. DS .$sub_root. $real_name. EXT);
    }
    
    // --------------------------------------------------------------------

    /**
    * Assign db objects to all Models
    *
    * @param   string $db_var
    * @return  void
    */
    protected static function _assign_db_objects($db_var = '')
    {
        if( ! is_object(this()))
        {
            return;
        }

        $OB = this();

        if (count(loader::$_models) == 0)
        {
            return;
        }

        foreach (loader::$_models as $model_name)
        {
            if( ! isset($OB->$model_name) ) return;

            if(is_object($OB->$model_name->$db_var)) // lazy loading
            {
                return;
            }

            if(is_object($OB->$db_var))
            {
                $OB->$model_name->$db_var = $OB->$db_var;
            }
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Assign this() objects to loaded
    * user libraries.
    * 
    * @param string $class library name
    * @return void
    */
    protected static function _assign_core_libraries($class)
    {               
        if( ! is_object(this()))
        {
            return;
        }
        
        foreach(array_keys(get_object_vars(this())) as $key) // This allows to using "$this" variable in all library files.
        {
            if ( ! isset(this()->{$class}->{$key}) AND $key != $class)
            {
                this()->{$class}->{$key} = &this()->$key;
            }             
        }
    }

}

// END Loader Class

/* End of file Loader.php */
/* Location: ./obullo/core/Loader.php */