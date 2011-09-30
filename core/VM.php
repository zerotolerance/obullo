<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         Obullo
 * @author          Obullo.com
 * @subpackage      Base.libraries
 * @copyright       Copyright (c) 2009 Ersin Guvenc.
 * @license
 * @since           Version 1.0
 * @filesource
 */

Class VMException extends CommonException {}

/**
 * Validation Model.
 *
 * @package         Obullo
 * @subpackage      Obullo.core
 * @category        Core Model
 * @version         0.1
 */

Class VM extends Model {

    public $property = array();  // user variables.
    public $errors   = array();  // validation errors.
    public $values   = array();  // filtered values.
    public $where    = FALSE;    // check where func used or not.

    /**
    * Construct
    *
    * @param  array $settings
    * @return void
    */
    public function __construct($settings = array())
    {
        if( ! isset($this->settings['fields']) OR ! isset($this->settings['database']))
        {
            throw new VMException('Check your model it must be contain $settings[\'fields\'] and $settings[\'database\'] array.');
        }

        $db = $settings['database'];

        loader::database($db);
        $this->db = this()->$db;

        parent::__construct();

        log_me('debug', "VM Class Initialized");
    }

    // --------------------------------------------------------------------

    /**
    * Grab validator object and set validation
    * rules automatically.
    *
    * @param  array $_GLOBALS can be $_POST, $_GET, $_FILES
    * @return boolean
    */
    public function validator($_GLOBALS = array(), $fields = array())
    {
        $validator = lib('Validator', '', TRUE);
        $validator->clear();

        if(count($_GLOBALS) == 0)
        {
            $_GLOBALS == $_REQUEST;
        }

        if(count($fields) == 0)
        {
            $fields = $this->settings['fields'];
        }

        $validator->set('_globals', $_GLOBALS);
        $validator->set('_callback_object', $this);

        $table = $this->settings['table'];

        foreach($fields as $key => $val)
        {
            if(is_array($val))
            {
                if(isset($val['rules']) AND $val['rules'] != '')
                {
                    $validator->set_rules($key, $val['label'], $val['rules']);
                }
            }
        }

        if($validator->run())   // Run validation
        {
            foreach($fields as $key => $val)  // Set filtered values
            {
                $this->values[$table][$key] = $this->set_value($key, $this->{$key});
            }

            return TRUE;
        }
        else
        {
            foreach($fields as $key => $val)  // Set validation errors..
            {
               if(isset($validator->_field_data[$key]['error']))
               {
                   $error = $validator->error($key, NULL, NULL);

                   if( ! empty($error))
                   {
                       $this->errors[$table][$key] = $error;
                   }
               }

               // Set filtered values
               $this->values[$table][$key] = $this->set_value($key, $this->{$key});
            }

            return FALSE;
        }
    }

    // --------------------------------------------------------------------

    /**
    * Validate the native GET or POST requests.
    * No insert, No update operations.
    *
    * @return bool
    */
    public function validate_request($fields = array())
    {
        $v_data = array();   // validation fields data
        $db     = $this->settings['database'];
        $table  = $this->settings['table'];
        $id     = (isset($this->settings['primary_key'])) ? $this->settings['primary_key'] : 'id';

        loader::lang('ob/vm');  // Load the language file that contains error messages

        $db_fields = $this->settings['fields'];
        if(count($fields) > 0)
        {
            unset($db_fields);
            foreach($fields as $f_key)
            {
                $db_fields[$f_key] = $this->settings['fields'][$f_key];
            }
        }

        foreach($db_fields as $k => $v)
        {
            if(isset($this->property[$k]))
            {
                if($this->settings['fields'][$k]['label'] == $this->$k)
                {
                    $this->$k = '';  // If value == label clear data !! This useful working with ajax validations.
                }
            }

            if($this->$k != '')
            {
                $v_data[$k] = $this->set_value($k, $this->$k);
            }
            else
            {
                $v_data[$k] = $this->set_value($k, i_get_post($k));
            }
        }

        return $this->validator($v_data, $db_fields);
    }

    // --------------------------------------------------------------------

    /**
    * GET filtered field value.
    *
    * @param string $field
    */
    public function value($field)
    {
        if(isset($this->values[$this->item('table')][$field]))
        {
            return $this->values[$this->item('table')][$field];
        }
    }

    // --------------------------------------------------------------------

    /**
    * Set Custom VM error for field.
    *
    * @param string $field
    */
    public function set_error($field, $error)
    {
        $this->errors[$this->item('table')][$field] = $error;
    }

    // --------------------------------------------------------------------

    /**
    * Get one error.
    *
    * @param string $field
    */
    public function error($field)
    {
        if(isset($this->errors[$this->item('table')][$field]))
        {
            return $this->errors[$this->item('table')][$field];
        }
    }

    // --------------------------------------------------------------------

    /**
    * Get Settings
    *
    * @param mixed $item
    * @param mixed $index
    */
    public function item($item)
    {
        if(strpos($item, '['))
        {
            $index = explode('[', $item);
            $index_item = str_replace(']', '', $index[1]);

            return $this->settings[$index[0]][$index_item];
        }
        else
        {
            return $this->settings[$item];
        }
    }

    // --------------------------------------------------------------------

    /**
    * Set requested property
    *
    * @param  string $key
    * @param  mixed  $val
    * @return void
    */
    public function __set($key, $val)
    {
        $this->property[$key] = $val;
    }

    // --------------------------------------------------------------------

    /**
    * Get requested property
    *
    * @param  string $property_name
    * @return mixed
    */
    public function __get($key)
    {
        if(isset($this->property[$key]))
        {
            return($this->property[$key]);
        }
        else
        {
            return(NULL);
        }
    }

    // --------------------------------------------------------------------

    public function before_save() {}
    public function after_save() {}

    /**
    * Do update if ID exists otherwise
    * do insert.
    *
    * @return  FALSE | Integer
    */
    public function save()
    {
        $v_data = array();   // validation fields data
        $s_data = array();   // mysql insert / update fields data
        $db     = $this->settings['database'];
        $table  = $this->settings['table'];
        $id     = (isset($this->settings['primary_key'])) ? $this->settings['primary_key'] : 'id';

        loader::lang('ob/vm');  // Load the language file that contains error messages

        $has_rules = FALSE;
        foreach($this->settings['fields'] as $k => $v)
        {
            if(isset($this->settings['fields'][$k]['rules']))  // validation used or not
            {
                $has_rules = TRUE;
            }

            if($this->$k != '')
            {
                $v_data[$k] = $this->set_value($k, $this->property[$k]);

                if(isset($this->settings['fields'][$k]['func']))
                {
                    $function_string = trim($this->settings['fields'][$k]['func'], '|');

                    if(strpos($function_string, '|') > 0)
                    {
                       $functions = explode('|', $this->settings['fields'][$k]['func']);
                    }
                    else
                    {
                       $functions = array($function_string);
                    }

                    foreach($functions as $name)
                    {
                        $s_data[$k] = $this->{'_'.$name}($v_data[$k]);
                    }
                }
                else
                {
                    $s_data[$k] = $v_data[$k];
                }
            }
            else
            {
                if(isset($this->property[$k])) // anyway send null data
                {
                    $s_data[$k] = '';
                }

                $v_data[$k] = $this->set_value($k, i_get_post($k));
            }

        }

        if($has_rules)  // if we have validation rules ..
        {
            $validator = $this->validator($v_data);  // do validation
        }
        else
        {
            $validator = TRUE;  // don't do validation
        }

        if($validator)  // if validation success !
        {
            if($this->$id != '')     // if isset ID do update ..
            {
                unset($s_data[$id]);

                if(count($this->{$db}->ar_where) == 0 AND count($this->{$db}->ar_wherein) == 0)
                {
                    $this->{$db}->where($id, $this->$id);
                }

                $this->errors[$table]['affected_rows'] = $this->{$db}->update($table, $s_data);

                if($this->errors[$table]['affected_rows'] == 1)
                {
                    $this->errors[$table]['success'] = 1;
                    $this->errors[$table]['msg']     = lang('vm_update_success');

                    lib('Validator')->clear();  // reset validator data

                    return TRUE;
                }
                elseif($this->errors[$table]['affected_rows'] == 0)
                {
                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_update_fail');

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;
                }
            }
            else   // ELSE do insert ..
            {
                $this->errors[$table]['affected_rows'] = $this->{$db}->insert($table, $s_data);

                if($this->errors[$table]['affected_rows'] == 1)
                {
                    $this->errors[$table]['success'] = 1;
                    $this->errors[$table]['msg']     = lang('vm_insert_success');

                    $this->values[$table][$id] = $this->{$db}->insert_id();  // add last inserted id.

                    lib('Validator')->clear();  // reset validator data

                    return $this->values[$table][$id];
                }
                else
                {
                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_insert_fail');

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;
                }

            }
        }

        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
    * Do type casting foreach value
    *
    * @param mixed $field
    * @param mixed $default
    * @return string
    */
    public function set_value($field, $default = '')
    {
        if( ! isset($this->settings['fields'][$field]['type']))
        {
            return $default;  // No type, return default value.
        }

        $type  = strtolower($this->settings['fields'][$field]['type']);
        $value = lib('Validator')->set_value($field, $default);

        if($type == 'string')
        {
            return form_prep($value);
        }

        if($type == 'int' OR $type == 'integer')
        return (int)$value;

        if($type == 'float')
        return (float)$value;

        if($type == 'double')
        return (double)$value;

        if($type == 'bool' OR $type == 'boolean')
        return (boolean)$value;

        if($type == 'null')
        return 'NULL';

        return $value;   // Unknown type.
    }

    // --------------------------------------------------------------------

    /**
    * Delete a record from current table
    * using ID
    *
    * @return boolean
    */
    public function delete()
    {
        $data  = array();
        $db    = $this->settings['database'];
        $table = $this->settings['table'];
        $id    = (isset($this->settings['primary_key'])) ? $this->settings['primary_key'] : 'id';

        lang_load('vm', '', 'base');  // Load the language file containing error messages

        if(isset($this->property[$id]))
        {
            $data[$id] = $this->$id;
        }

        $validator = $this->validator($data);

        if($validator)
        {
            if($this->$id != '')    // do delete ..
            {
                if(count($this->{$db}->ar_where) == 0 AND count($this->{$db}->ar_wherein) == 0)
                {
                    $this->{$db}->where($id, $this->$id);
                }

                $this->errors[$table]['affected_rows'] = $this->{$db}->delete($table);

                if($this->errors[$table]['affected_rows'] == 1)
                {
                    $this->errors[$table]['success'] = 1;
                    $this->errors[$table]['msg']     = lang('vm_delete_success');

                    return TRUE;
                }
                elseif($this->errors[$table]['affected_rows'] == 0)
                {
                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_delete_fail');

                    return FALSE;
                }
            }
        }


        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
    * Access active record last_query()
    * function.
    *
    * @param  bool $prepare
    * @return string
    */
    public function last_query($prepare = FALSE)
    {
        $db = $this->settings['database'];

        return $this->{$db}->last_query($prepare);
    }

    // --------------------------------------------------------------------

    /**
    * Covert string to md5 hash
    *
    * @param  string
    */
    private function _md5($str)
    {
        return md5($str);
    }

}

// END Validation Model Class

/* End of file VM.php */
/* Location: ./obullo/core/VM.php */