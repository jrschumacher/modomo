<?php
  /**
   * Mongo Record, a simple Mongo ORM for PHP with ActiveRecord-like features.
   * 
   * @author     Ryan Schumacher <https://github.com/jrschumacher/>
   * @author     Lu Wang <https://github.com/lunaru/>
   * @license    See LICENSE
   * @version    1.5
   */

  namespace MongoRecord;

  /**
   * Base Mongo Record, main abstraction layer for the Mongo Record
   * 
   * Support for events:
   *  - afterNew
   *  - afterNewSave
   *  - beforeValidation
   *  - afterValidation
   *  - beforeSave
   *  - afterSave
   *  - beforeRemove
   *  - afterRemove
   * 
   * @package MongoRecord
   */
  abstract class BaseMongoRecord extends CoreMongoRecord {
    
    /**
     * @var array
     */
    public $__meta = array(
      'strict_schema' => FALSE
    );
    
    /**
     * 
     */
    public $_errors = array();
    
    /**
     * @uses stores the attributes for the record
     * @var array
     */
  	public $attributes = array();
    
    /**
     * @var array
     */
  	protected $errors = array();
    
    /**
     * @uses defined upon creation of record
     * @var BOOL
     */
  	private $new = TRUE;
    
    /**
     * @uses if data has changed
     * @var BOOL
     */
    private $dirty = FALSE;
    
    /**
     * 
     * 
     */
    protected static $collection = NULL;
    
    /**
     * 
     */
    private static $event_callbacks = array();
    
    /*----- Static -----*/

    /**
     * Find all records within Collection
     * 
     * @link http://www.php.net/manual/en/mongocollection.find.php
     * @param array $query query to search Mongo collection
     * @param array $options options to excute on the MongoCursor {@link http://www.php.net/manual/en/class.mongocursor.php}
     * return array $results the docments which were found instantiated as object {@link BaseMongoRecord::instantiate()}
     */
    public static function find($query = array(), $options = array()) {
      $class_name = get_called_class();

      // Anonymous MongoRecord
      $collection_name = NULL;
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(2);
      }
      
      $results = array();
      
      // Find the requested objects
      $documents = self::mongoGetCollection($collection_name)->find($query);
      
      // Sort based on the options
      if(isset($options['sort'])) {
        $documents->sort($options['sort']);
      }
      
      // Offset based on the options
      if(isset($options['offset'])) {
        $documents->skip($options['offset']);
      }
      
      // Limit based on the options
      if(isset($options['limit'])) {
        $documents->limit($options['limit']);
      }

      // Set timeout {@link BaseMongoRecord::setFindTimeout()}
      $documents->timeout($class_name::$findTimeout);
    
      // Instantiate each document found {@link BaseMongoRecord::instantiate()}
      while($documents->hasNext()) {
        $document = $documents->getNext();
        $results[] = self::instantiate($document, $collection_name);
      }
      
      // Return records
      return $results;
    }

    /**
     * Find one record within Collection
     * 
     * Uses find to search for a single record and forces $options['limit'] = 1
     * 
     * @link http://www.php.net/manual/en/mongocollection.find.php
     * @param array $query query to search Mongo collection
     * @param array $options options to excute on the MongoCursor {@link http://www.php.net/manual/en/class.mongocursor.php}
     * return BaseMongoRecord $results the docments which were found instantiated as object {@link BaseMongoRecord::instantiate()}
     */
    public static function findOne($query = array(), $options = array()) {
      
      // Anonymous MongoRecord
      $collection_name = NULL;
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(2);
      }
      
      // Force limit as 1
      $options['limit'] = 1;

      // Runs a find query
      $results = self::find($query, $options, $collection_name);
      
      // Return the record
      if($results) {
        return current($results);
      }
      
      // None found
      return NULL;
    }

    /**
     * Count the number of records
     * 
     * @param array $query
     * @return int number of records
     */
    public static function count($query = array()) {
      // Anonymous MongoRecord
      $collection_name =  NULL;
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(1);
      }
      
      // Count the number of documents
      $collection =& self::mongoGetCollection($collection_name);
      return $collection->count($query);
    }

    /**
     * Instantiate document as record
     * 
     * @see BaseMongoRecord::find()
     * @param array result from find query
     * @return mixed returns the record as object or NULL if not found
     */
    protected static function instantiate($document) {
      // Empty document
      if(empty($document)) {
        return NULL;
      }
      
      // Anonymous MongoRecord
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(1);
        return new MongoRecord($collection_name, $document, FALSE);
      }
      
      // Instantiate method
      $class_name = get_called_class();
      return new $class_name($document, FALSE);
    }
    
    /**
     * Register event
     */
    public static function registerEvent($event, $callback) {
      // Anonymous MongoRecord
      $collection_name = NULL;
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(2);
        $callbacks =& self::$event_callbacks[$collection_name];
      }
      else {
        $callbacks =& self::$event_callbacks;
      }
      
      // Callable
      if(!is_callable($callback)) {
        throw new BaseMongoRecordException('Callback must be callable');
      }
      
      if(empty($callbacks[$event])) {
        $callbacks[$event] = array();
      }
      $callbacks[$event][] = $callback;
    }
    
    /**
     * Trigger event
     */
    protected static function triggerEvent($event, &$scope) {
      // Anonymous MongoRecord
      $collection_name = NULL;
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(2);
        $callbacks =& self::$event_callbacks[$collection_name];
      }
      else {
        $callbacks =& self::$event_callbacks;
      }
      
      if(isset($callbacks[$event]) && is_array($callbacks[$event])) {
        foreach($callbacks[$event] as $callback) {
          if(is_callable($callback)) {
            call_user_func($callback, $scope);
          }
        }
      }
      
      // Backwards compatibility
      if(is_callable(array($scope, $event))) {
        $scope->{$event}();
      }
    }
    
    /*----- Non-static -----*/

    /**
     * Constructor for the MongoRecord
     * 
     * On construction the object is either new or existing. Attributes can be
     * added to the initalizing object.
     * 
     * @param array $attributes initalizing values
     * @param bool $new whether this record is "new" or existing
     */
    public function __construct($attributes = array(), $new = TRUE) {
      // Get collection name
      $class_name = get_called_class();
      
      // Anonymous MongoRecord
      $collection_name = NULL;
      if(self::mongoIsAnonymous()) {
        $collection_name = func_get_arg(2);
      }
      
      $this->__meta['class_name'] = $class_name;
      $this->__meta['collection_name'] = self::mongoGetCollection($collection_name)->getName();

      // If new
  		if($new) {
        $this->new = TRUE;
        
        $current_key = key($attributes);
        if(strpos($current_key, '$') === 0) {
          if($current_key === '$intersectArray' && is_array($attributes[$current_key]) && count($attributes[$current_key]) === 2) {
            $attributes = MongoRecordHelper::intersectArray($attributes[$current_key][0], $attributes[$current_key][1]);
          }
          
          if($current_key === '$merge' && is_array($attributes[$current_key]) && count($attributes[$current_key]) == 2) {
            $attributes = MongoRecordHelper::mergeArray($attributes[$current_key][0], $attributes[$current_key][1]);
          }
        }
        
        // Set attributes based using their setters
        foreach($attributes as $attribute => $value) {
          $this->setter($attribute, $value);
        }
        
        // Trigger after new event
  			self::triggerEvent('afterNew', $this, $this->__meta['collection_name']);
      }
      else {
        $this->new = FALSE;
        $this->attributes = $attributes;
      }
  	}
      
    /**
     * __call, used to do getters or setters for the record attributes
     * 
     * @param string $method the method called
     * @param array $attributes the attributes of the called method
     * @return mixed if a getter the
     */
    public function __call($method, $arguments) {
      
      // Is this a getter or setter
      $getter_setter = strtolower(substr($method, 0, 3));
      if(strlen($method) < 4 || ($getter_setter != 'get' && $getter_setter != 'set')) {
        return $this;
      }

      // What is the get/set class attribute
      $attribute = Inflector::getInstance()->underscore(substr($method, 3));

      // Getter method
      if($getter_setter == "get") {
        return $this->getter($attribute);
      }

      // Setter method
      if($getter_setter == "set") {
        $value = NULL;
        if(func_num_args() > 0) $value = func_get_arg(0);
        $this->setter($attribute, $value);
      }
      
      return $this;
    }

    /**
     * Set attributes of Mongo record
     */
    public function __set($attribute, $value = NULL) {
      $this->setter($attribute, $value);
    }
    
    /**
     * Get attributes of Mongo record
     */
    public function __get($attribute) {
      return $this->getter($attribute);
    }
    
    /**
     * Get all attributes
     */
    public function toArray() {
      return $this->attributes;
    }

    /**
     * Get id of record
     * 
     * @return string the id of the Mongo document
     */
    public function getId($to_string = FALSE) {
      if(is_object($this->attributes['_id'])) {
        if($to_string) {
          return $this->attributes['_id']->__toString();
        }
        return $this->attributes['_id'];
      }
      return FALSE;
    }
    
    /**
     * Set id of record
     * 
     * @param string $id new id for the Mongo document
     */
    public function setId($id) {
      if(is_string($id)) {
        $this->attributes['_id'] = new \MongoId($id); 
      } else if(get_class($id) === 'MongoId') {
        $this->attributes['_id'] = $id;
      }
      return FALSE;
    }

    /**
     * Validate this object
     * 
     * Runs through the validation routines of each attribute as well as triggering
     * the validation events.
     * 
     * @return bool if is valid
     */
  	public function validate() {
  	  // Trigger before validation event
  	  self::triggerEvent('beforeValidation', $this, $this->__meta['collection_name']);
      
      // Check if is valid
  		$is_valid = $this->isValid();
      
      // Trigger after validation event
  		self::triggerEvent('afterValidation', $this, $this->__meta['collection_name']);
      
  		return $is_valid;
  	}
    
    /**
     * Add error
     */
    public function _addError($key, $message = '') {
      if(empty($message)) {
        $message = 'Invalid data.';
      }
      
      if(empty($this->_errors[$key])) {
        $this->_errors[$key] = array();
      }
      $this->_errors[$key][] = $message;
    }

    /**
     * Save the object to the Mongo Record
     * 
     * Runs through the save procedure: validate the object, trigger events, save
     * record to Mongo collection
     * 
     * @return bool whether the save was successful
     */
  	public function save() {
  	  // Validate the object
  		if(!$this->validate()) {
  			return FALSE;
      }

      // Trigger before save event
  		self::triggerEvent('beforeSave', $this, $this->__meta['collection_name']);
      
      // Save the object to the mongo collection
  		$collection = self::mongoGetCollection($this->__meta['collection_name']);
  		$collection->save($this->attributes);
      
      // Trigger after save new event
      if($this->new) {
        self::triggerEvent('afterNewSave', $this, $this->__meta['collection_name']);
      }

      // No longer a new object
  		$this->new = FALSE;
      
      // Trigger after save event
  		self::triggerEvent('afterSave', $this, $this->__meta['collection_name']);

  		return TRUE;
  	}

    /**
     * Remove 
     * 
     * Removes the object from the Mongo Record
     */
    public function remove() {
      // Trigger before remove event
      self::triggerEvent('beforeRemove', $this, $this->__meta['collection_name']);

      // If not a new object then remove it from the mongo collection
      if(!$this->new) {
        $collection = self::mongoGetCollection($this->__meta['collection_name']);
        $collection->remove(array('_id' => $this->attributes['_id']));
      }
      
      // Trigger after remove event
      self::triggerEvent('afterRemove', $this, $this->__meta['collection_name']);
      
      return TRUE;
    }

    /**
     * Destroy
     * 
     * Depreciated for remove instead to be more consistant with the Mongo methods
     * 
     * @link http://www.mongodb.org/display/DOCS/Removing
     * @depreciated
     */
    public function destroy() {
      trigger_error('BaseMongoRecord::destroy() has been depreciated. Use BaseMongoRecord::remove() instead.', E_USER_DEPRECATED);
      self::triggerEvent('beforeDestroy', $this, $this->__meta['collection_name']);
      $this->remove();
    }

    /**
     * Setter
     */
    protected function setter($attribute, $value = NULL) {
      $method = 'set' . Inflector::getInstance()->camelize($attribute);
      if(method_exists($this, $method)) {
        $this->{$method}($value);
      }
      else {
        if($this->__meta['strict_schema'] === TRUE && !isset($this->attributes[$attribute])) {
          return FALSE;
        }
        
        $this->attributes[$attribute] = $value;
      }
    }
    
    /**
     * Getter
     */
    protected function getter($attribute) {
      if(! isset($this->attributes[$attribute])) {
        return NULL;
      }
          
      $method = 'get' . Inflector::getInstance()->camelize($attribute);
      if(method_exists($this, $method)) {
        return $this->{$method}();
      }
      return $this->attributes[$attribute];    
    }

    /**
     * Is valid, validate the attributes
     * 
     * @return bool is valid 
     */
  	protected function isValid() {
  		$methods = get_class_methods($this->__meta['class_name']);
  	
  		foreach ($methods as $method) {
  			if (strcasecmp(substr($method, 0, 9), 'validates') === 0) {
  			  $attribute = $this->getter(strtolower(substr($method, 9)));
  				if($this->{$method}($attribute) !== TRUE) {
  					return FALSE;
  				}
  			}
  		}

  		return TRUE; 
  	}
    
    /**
     * Get attributes
     */
    public function getAttributes($options = array()) {
      $opts = array_merge(array(
        'id' => 'object'
      ), $options);
      
      $attributes = $this->attributes;
      if(isset($opts['id']) && $opts['id'] === 'string') {
        $attributes['id'] = $attributes['_id']->{'$id'};
        unset($attributes['_id']); 
      } else {
        $attributes['id'] = $attributes['_id'];
        unset($attributes['_id']);
      }
      return $attributes;
    }
    
    /**
     * To String
     */
    public function __toString() {
      return json_encode($this->attributes);
    }
  	
  }
