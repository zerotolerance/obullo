<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Mongo DB Class.
 * Derived from MongoDB Active Record Library.
 *
 * A library to interface with the NoSQL database MongoDB. For more information see http://www.mongodb.org
 *
 * @package		Obullo
 * @author		Alex Bilbie | www.alexbilbie.com | alex@alexbilbie.com ( Original Library )
 * @author		Ersin Güvenç. ( Porting to Obullo )
 * @copyright           Copyright (c) 2012, Alex Bilbie.
 * @license		http://www.opensource.org/licenses/mit-license.php
 * @link		http://alexbilbie.com
 * @version		Version 0.5.2
 *
 */

Class OB_Mongo_db {

    private $connection;
    private $db;
    private $connection_string;

    private $host;
    private $port;
    private $user;
    private $pass;
    private $dbname;
    private $persist;
    private $persist_key;
    private $query_safety = 'safe';

    private $selects  = array();
    public  $wheres   = array(); // Public to make debugging easier
    private $sorts    = array();
    public  $updates  = array(); // Public to make debugging easier

    private $limit = 999999;
    private $offset = 0;
    private $last_inserted_id = ''; // Last inserted id.
    
    private $collection = ''; // Set collection name using $this->db->from() ?

    /**
    * Constructor
    * 
    * Automatically check if the Mongo PECL extension has been installed/enabled.
    * Generate the connection string and establish a connection to the MongoDB.
    * 
    * @throws Exception 
    */
    public function __construct()
    {
        if ( ! class_exists('Mongo'))
        {
            throw new Exception("The MongoDB PECL extension has not been installed or enabled.");
        }
        
        $this->connection_string();
    }

    // --------------------------------------------------------------------

    /**
    * Switch from default database to a different db
    * 
    * $this->db->switch_db('foobar');
    * 
    * @param type $database
    * @return type 
    */
    public function switch_db($database = '')
    {
        if (empty($database))
        {
            throw new Exception("To switch MongoDB databases, a new database name must be specified.");
        }

        $this->dbname = $database;

        try
        {
            $this->db = $this->connection->{$this->dbname};
            return (TRUE);
        }
        catch (Exception $e)
        {
            throw new Exception("Unable to switch Mongo Databases: ".$e->getMessage());
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Determine which fields to include OR which to exclude during the query process.
     * Currently, including and excluding at the same time is not available, so the
     * $includes array will take precedence over the $excludes array.
     * 
     * If you want to only choose fields to exclude, leave $includes an empty array().
     * 
     * @usage: $this->db->select(array('foo', 'bar'))->get('foobar');
     * 
     * @param type $includes
     * @param array $excludes
     * @return type 
     */
    public function select($includes = array(), $excludes = array())
    {
        if(is_string($includes) AND strpos($includes, ',') > 0)
        {
            $includes = explode(',', $includes);
            $includes = array_map('trim', $includes);
        }
        
        if ( ! is_array($includes))
        {
            $includes = array();
        }

        if ( ! is_array($excludes))
        {
            $excludes = array();
        }

        if ( ! empty($includes))
        {
            foreach ($includes as $col)
            {
                $this->selects[$col] = 1;
            }
        }
        else
        {
            foreach ($excludes as $col)
            {
                $this->selects[$col] = 0;
            }
        }

        return ($this);
    }

    // --------------------------------------------------------------------
    
    public function from($collection = '')
    {
        $this->collection = $collection;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get the documents based on these search parameters.  The $wheres array should 
     * be an associative array with the field as the key and the value as the search
     * criteria.
     * 
     * @usage : $this->db->where(array('foo' => 'bar'))->get('foobar');
     * 
     * @param type $wheres
     * @param type $value
     * @return \OB_Mongo_db 
     */
    public function where($wheres, $value = null)
    {
        if (is_array($wheres))
        {
            foreach ($wheres as $wh => $val)
            {
                $this->wheres[$wh] = $val;
            }
        }
        else
        {
            $this->wheres[$wheres] = $value;
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Get the documents where the value of a $field may be something else
     * 
     * @usage : $this->db->or_where(array('foo'=>'bar', 'bar'=>'foo'))->get('foobar');
     * 
     * @param type $wheres
     * @return type 
     */
    public function or_where($wheres, $value = null)
    {
        if (is_array($value))
        {
            foreach ($value as $wh => $val)
            {
                $this->wheres['$or'][][$wheres] = $val;
            }
        }
        else
        {
            $this->wheres['$or'][][$wheres] = $value;
        }
        
        return ($this);
    }

    // --------------------------------------------------------------------

    /**
     * Get the documents where the value of a $field is in a given $in array().
     * 
     * @usage : $this->db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
     * 
     * @param type $field
     * @param type $in
     * @return type 
     */
    public function where_in($field = "", $in = array())
    {
        $this->_where_init($field);
        $this->wheres[$field]['$in'] = $in;
        
        return ($this);
    }

    // --------------------------------------------------------------------
    
    /**
     * Sort the documents based on the parameters passed. To set values to descending order,
     * you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
     * set to 1 (ASC).
     * 
     * @usage : $this->db->order_by(array('foo' => 'ASC'))->get('foobar');
     * 
     * @param type $fields
     * @return type 
     */
    public function order_by($fields = array())
    {
        foreach ($fields as $col => $val)
        {
            if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
            {
                $this->sorts[$col] = -1;
            }
            else
            {
                $this->sorts[$col] = 1;
            }
        }
        
        return ($this);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Limit the result set to $x number of documents.
     * 
     * @usage : $this->db->limit($x);
     * 
     * @param type $x
     * @return type 
     */
    public function limit($x = 99999)
    {
        if ($x !== NULL && is_numeric($x) && $x >= 1)
        {
            $this->limit = (int) $x;
        }
        
        return ($this);
    }

    // --------------------------------------------------------------------
   
    /**
     * Offset the result set to skip $x number of documents.
     * 
     * @usage : $this->db->offset($x);
     * 
     * @param type $x
     * @return type 
     */
    public function offset($x = 0)
    {
        if ($x !== NULL && is_numeric($x) && $x >= 1)
        {
            $this->offset = (int) $x;
        }
        
        return ($this);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * @usage : $this->select()->from('collection')->find($criteria);
     * 
     * @param type $criteria
     * @param type $fields
     * @return type
     * @throws Exception 
     */
    public function find($criteria = array(), $fields = array())
    {
        if($this->collection == '')
        {
            throw new Exception('You need to set a collection name using <b>$this->db->from("collection")</b> function.');
        }
        
        $documents = $this->db->{$this->collection}->find($criteria, array_merge($this->selects, $fields))
                ->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);
        
        $this->_reset_select();         // Reset
        
        return $documents;
    }
    
    // --------------------------------------------------------------------
    
     /**
     * @usage : $this->select()->from('collection')->find_one($criteria);
     * 
     * @param type $criteria
     * @param type $fields
     * @return type
     * @throws Exception 
     */
    public function find_one($criteria = array(), $fields = array())
    {
        if($this->collection == '')
        {
            throw new Exception('You need to set a collection name using <b>$this->db->from("collection")</b> function.');
        }
        
        $documents = $this->db->{$this->collection}->findOne($criteria, array_merge($this->selects, $fields))
                ->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);
        
        $this->_reset_select();         // Reset
        
        return $documents;
    }
    
    // --------------------------------------------------------------------
   
    /**
     * Get the documents based upon the passed parameters.
     * 
     * @usage : $this->db->get('foo');
     * 
     * @param type $collection
     * @return type
     * @throws Exception 
     */
    public function get($collection = '')
    {
        $collection = (empty($this->collection)) ? $collection : $this->collection;
        
        if (empty($collection))
        {
            throw new Exception("In order to retrieve documents from MongoDB, a collection name must be passed.");
        }
        
        // print_r($this->wheres);

        $documents = $this->db->{$collection}->find($this->wheres, $this->selects)
                ->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);
        
        $this->_reset_select();         // Reset
        
        return $documents;
    }

    // --------------------------------------------------------------------
    
    /**
     * Insert a new document into the passed collection
     *
     * @usage : $this->mongo_db->insert('foo', $data = array());
     * 
     * @param string $collection
     * @param array $insert
     * @return int affected rows
     * @throws Exception 
     */
    public function insert($collection = "", $insert = array())
    {
        if (empty($collection))
        {
            throw new Exception("No Mongo collection selected to insert into.");
        }

        if (count($insert) == 0 || ! is_array($insert))
        {
            throw new Exception("Nothing to insert into Mongo collection or insert is not an array.");
        }

        try
        {
            $this->db->{$collection}->insert($insert, array($this->query_safety	 => TRUE));
            
            if (isset($insert['_id']))
            {
                $this->last_inserted_id = $insert['_id'];
                
                return count(array_pop($insert)); // affected rows.
            }
            else
            {
                return (FALSE);
            }
        }
        catch (MongoCursorException $e)
        {
            throw new Exception("Insert of data into MongoDB failed: ".$e->getMessage());
        }
    }

    // --------------------------------------------------------------------
    
    /**
     * Batch Insert
     * 
     * Insert a multiple new document into the passed collection.
     * 
     * @usage : $this->mongo_db->batch_insert('foo', $data = array());
     * 
     * @param type $collection
     * @param type $insert
     * @return type
     * @throws Exception 
     */
    public function batch_insert($collection = "", $insert = array())
    {
        if (empty($collection))
        {
            throw new Exception("No Mongo collection selected to insert operation.");
        }

        if (count($insert) == 0 || ! is_array($insert))
        {
            throw new Exception("Nothing to insert into Mongo collection or insert is not an array.");
        }

        try
        {
            $this->db->{$collection}->batchInsert($insert, array($this->query_safety => TRUE));
            
            if (isset($insert['_id']))
            {
                return ($insert['_id']);
            }
            else
            {
                return (FALSE);
            }
        }
        catch (MongoCursorException $e)
        {
            throw new Exception("Insert of data into MongoDB failed: ".$e->getMessage());
        }
    }

    // --------------------------------------------------------------------
    
    /**
     * Updates multiple document
     * 
     * @usage: $this->mongo_db->update('foo', $data = array());
     * 
     * @param string $collection
     * @param array $data
     * @param array $options
     * @return int affected rows
     * @throws Exception 
     */
    public function update($collection = "", $data = array(), $options = array())
    {
        if (empty($collection))
        {
            throw new Exception("No Mongo collection selected to update.");
        }

        if (is_array($data) && count($data) > 0)
        {
            $this->updates = array_merge($data, $this->updates);
        }

        if (count($this->updates) == 0)
        {
            throw new Exception("Nothing to update in Mongo collection or update is not an array.");
        }

        // Multiple update behavior like MYSQL.
        
        try
        {
            $options = array_merge($options, array($this->query_safety => TRUE, 'multiple' => FALSE));
            
            $this->db->{$collection}->update($this->wheres, array('$set' => $this->updates), $options);
            
            $this->_reset_select();
            
            return $this->db->{$collection}->find($this->updates)->count();
        }
        catch (MongoCursorException $e)
        {
            throw new Exception("Update of data into MongoDB failed: ".$e->getMessage());
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Increments the value of a field.
     * 
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->inc(array('num_comments' => 1))->update('blog_posts');
     * 
     * @param type $fields
     * @param type $value
     * @return \OB_Mongo_db 
     */
    public function inc($fields = array(), $value = 0)
    {
        $this->_update_init('$inc');

        if (is_string($fields))
        {
            $this->updates['$inc'][$fields] = $value;
        }

        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $this->updates['$inc'][$field] = $value;
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------
    
    /**
     * Decrements the value of a field.
     * 
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->dec(array('num_comments' => 1))->update('blog_posts');
     * 
     * @param type $fields
     * @param type $value
     * @return \OB_Mongo_db 
     */
    public function dec($fields = array(), $value = 0)
    {
        $this->_update_init('$inc');

        if (is_string($fields))
        {
            $this->updates['$inc'][$fields] = $value;
        }

        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $this->updates['$inc'][$field] = $value;
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------
    
    /**
     * Sets a field to a value.
     * 
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->set('posted', 1)->update('blog_posts');
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->set(array('posted' => 1, 'time' => time()))->update('blog_posts');
     * 
     * @param type $fields
     * @param type $value
     * @return \OB_Mongo_db 
     */
    public function set($fields, $value = NULL)
    {
        $this->_update_init('$set');

        if (is_string($fields))
        {
            $this->updates['$set'][$fields] = $value;
        }

        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $this->updates['$set'][$field] = $value;
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------
    
    /**
     * Unsets a field (or fields).
     * 
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->unset('posted')->update('blog_posts');
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->set(array('posted','time'))->update('blog_posts');
     * 
     * @param type $fields
     * @return \OB_Mongo_db 
     */
    public function unset_field($fields)
    {
        $this->_update_init('$unset');

        if (is_string($fields))
        {
            $this->updates['$unset'][$fields] = 1;
        }

        elseif (is_array($fields))
        {
            foreach ($fields as $field)
            {
                $this->updates['$unset'][$field] = 1;
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------
    
    /**
     * Adds value to the array only if its not in the array already.
     * 
     * @usage: $this->db->where(array('blog_id'=>123))->addtoset('tags', 'php')->update('blog_posts');
     * @usage: $this->db->where(array('blog_id'=>123))->addtoset('tags', array('php', 'obullo', 'mongodb'))->update('blog_posts');
     * 
     * @param type $field
     * @param type $values
     * @return \OB_Mongo_db 
     */
    public function addtoset($field, $values)
    {
        $this->_update_init('$addToSet');

        if (is_string($values))
        {
            $this->updates['$addToSet'][$field] = $values;
        }

        elseif (is_array($values))
        {
            $this->updates['$attToSet'][$field] = array('$each' => $values);
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Pushes values into a field (field must be an array).
     * 
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->push('comments', array('text'=>'Hello world'))->update('blog_posts');
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->push(array('comments' => array('text'=>'Hello world')), 'viewed_by' => array('Alex')->update('blog_posts');
     * 
     * @param type $fields
     * @param type $value
     * @return \OB_Mongo_db 
     */
    public function push($fields, $value = array())
    {
        $this->_update_init('$push');

        if (is_string($fields))
        {
            $this->updates['$push'][$fields] = $value;
        }

        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $this->updates['$push'][$field] = $value;
            }
        }

        return $this;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Pops the last value from a field (field must be an array).
     *  
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->pop('comments')->update('blog_posts');
     * @usage: $this->mongo_db->where(array('blog_id'=>123))->pop(array('comments', 'viewed_by'))->update('blog_posts');
     * 
     * @param type $field
     * @return \OB_Mongo_db 
     */
    public function pop($field)
    {
        $this->_update_init('$pop');

        if (is_string($field))
        {
            $this->updates['$pop'][$field] = -1;
        }

        elseif (is_array($field))
        {
            foreach ($field as $pop_field)
            {
                $this->updates['$pop'][$pop_field] = -1;
            }
        }

        return $this;
    }
    
    // --------------------------------------------------------------------

    /**
     * Removes by an array by the value of a field.
     * 
     * @usage: $this->mongo_db->pull('comments', array('comment_id'=>123))->update('blog_posts');
     * 
     * @param type $field
     * @param type $value
     * @return \OB_Mongo_db 
     */
    public function pull($field = "", $value = array())
    {
        $this->_update_init('$pull');

        $this->updates['$pull'] = array($field => $value);

        return $this;
    }

    // --------------------------------------------------------------------
    
    /**
     * Delete all documents from the passed collection based upon certain criteria.
     * 
     * @usage : $this->mongo_db->delete_all('foo', $data = array());
     * 
     * @param string $collection
     * @return int affected rows.
     * @throws Exception 
     */
    public function delete($collection = "")
    {
        if (empty($collection))
        {
            throw new Exception("No Mongo collection selected to delete.");
        }

        if (isset($this->wheres['_id']) AND ! ($this->wheres['_id'] instanceof MongoId))
        {
            $this->wheres['_id'] = new MongoId($this->wheres['_id']);
        }

        try
        {
            $affected_rows = $this->db->{$collection}->find($this->wheres)->count();
            
            $this->db->{$collection}->remove($this->wheres, array($this->query_safety => TRUE, 'justOne' => FALSE));
            
            $this->_reset_select();
            
            return $affected_rows;
        }
        catch (MongoCursorException $e)
        {
            throw new Exception("MongoDB data delete operation failed: ".$e->getMessage());
        }
    }

    // --------------------------------------------------------------------
    
    /**
     * Establish a connection to MongoDB using the connection string generated in
     * the connection_string() method.  If 'mongo_persist_key' was set to true in the
     * config file, establish a persistent connection.
     * 
     * We allow for only the 'persist'
     * option to be set because we want to establish a connection immediately.
     * 
     * @return type
     * @throws Exception 
     */
    public function connect()
    {
        $options = array();
        if ($this->persist === TRUE)
        {
            $options['persist'] = isset($this->persist_key) && !empty($this->persist_key) ? $this->persist_key : 'ob_mongo_persist';
        }
        
        try
        {
            $this->connection = new Mongo($this->connection_string, $options);
            $this->db = $this->connection->{$this->dbname};
            
            return ($this);	
        } 
        catch (MongoConnectionException $e)
        {
            throw new Exception("Unable to connect to MongoDB: ".$e->getMessage());
        }
    }

    // --------------------------------------------------------------------
    
    /**
     * Build the connection string from the config file.
     * 
     * @throws Exception 
     */
    private function connection_string() 
    {
        $config = get_config('mongodb');

        if($config['database'] == '')
        {
            throw new Exception('Please set a <b>$mongodb[\'database\']</b> from <b>/app/config/mongodb.php</b>.');
        }
        $this->host         = $config['host'];
        $this->port         = $config['port'];
        $this->user         = $config['username'];
        $this->pass         = $config['password'];
        
        $this->dbname       = $config['database'];
        $this->persist      = $config['persist'];
        $this->persist_key  = $config['persist_key'];
        $this->query_safety = $config['query_safety'];
        
        $dbhostflag = (bool)$config['host_db_flag'];

        $connection_string = "mongodb://";

        if (empty($this->host))
        {
            throw new Exception("You need to specify a hostname connect to MongoDB.");
        }

        if (empty($this->dbname))
        {
            throw new Exception("You need to specify a database name connect to MongoDB.");
        }

        if ( ! empty($this->user) && ! empty($this->pass))
        {
            $connection_string .= "{$this->user}:{$this->pass}@";
        }

        if (isset($this->port) && ! empty($this->port))
        {
            $connection_string .= "{$this->host}:{$this->port}";
        }
        else
        {
            $connection_string .= "{$this->host}";
        }

        if ($dbhostflag === TRUE)
        {
            $this->connection_string = trim($connection_string) . '/' . $this->dbname;
        }
        else
        {
            $this->connection_string = trim($connection_string);
        }
    }

    // --------------------------------------------------------------------

    /**
     *  Resets the class variables to default settings
     */
    public function _reset_select()
    {
        $this->selects	= array();
        $this->updates	= array();
        $this->wheres	= array();
        $this->limit	= 999999;
        $this->offset	= 0;
        $this->sorts	= array();
        $this->find     = FALSE;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Prepares parameters for insertion in $wheres array().
     * 
     * @param type $param 
     */
    private function _where_init($param)
    {
        if ( ! isset($this->wheres[$param]))
        {
            $this->wheres[ $param ] = array();
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Prepares parameters for insertion in $updates array().
     * 
     * @param type $method 
     */
    private function _update_init($method)
    {
        if ( ! isset($this->updates[$method]))
        {
            $this->updates[ $method ] = array();
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Get last inserted Mongo id.
     * 
     * @return string
     */
    public function insert_id()
    {
        return $this->last_inserted_id;
    }
    
    // --------------------------------------------------------------------
    // Fake functions. Do not remove them.
    
    public function transaction() {}
    public function commit() {}
    public function rollback() {}
    
}
// END Mongo_db Class

/* End of file Mongo_db.php */
/* Location: ./obullo/libraries/mongo_db.php */