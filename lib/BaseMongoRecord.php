<?php
/**
 * Base Mongo Record, main abstraction layer for the Mongo Record library
 * 
 * This files defines the main abstaction layer for the Mongo Record libary which
 * includes the loading of required libraries:
 *   - Mongo Record
 *   - Inflector
 * 
 * @author Lu Wang <https://github.com/lunaru/>
 * @version 1.0.1
 * @package MongoRecord
 */

/**
 * Require Mongo Record Exception
 */
require_once('MongoRecordExceptions.php');
 
/**
 * Require Core Mongo Record
 */
require_once('CoreMongoRecord.php');
 
 /**
  * Require Mongo Record
  */
//require_once('MongoRecord.php');

/**
 * Require Inflector
 */
require_once('Inflector.php');

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
   * @uses stores the attributes for the record
   * @var array
   */
	protected $attributes = array();
  
  /**
   * @var array
   */
	public $errors = array();
  
  /**
   * @uses defined upon creation of record
   * @var BOOL
   */
	private $new = TRUE;
  
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
    $results = array();
    
    // Find the requested objects
    $documents = self::mongoGetCollection()->find($query);
    $className = get_called_class();
    
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
    $documents->timeout($className::$findTimeout);
  
    // Instantiate each document found {@link BaseMongoRecord::instantiate()}
    while($documents->hasNext()) {
      $document = $documents->getNext();
      $results[] = self::instantiate($document);
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
    // Force limit as 1
    $options['limit'] = 1;

    // Runs a find query
    $results = self::find($query, $options);
    
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
    // Count the number of documents
    $collection = self::mongoGetCollection();
    $count = $collection->count($query);

    return $count;
  }

  /**
   * Instantiate document as record
   * 
   * @see BaseMongoRecord::find()
   * @param array result from find query
   * @return mixed returns the record as object or NULL if not found
   */
  private static function instantiate($document) {
    if($document) {
      $class_name = get_called_class();
      $obj = new $class_name(array('a' => 'b'), FALSE);
      return new Model\User($document, FALSE);
    }
    
    return NULL;
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
		$this->new = $new;

    // Set attributes based using their setters
    foreach($attributes as $attribute => $value) {
      $this->setter($attribute, $value);
    }

    // Trigger after new event
		if($new) {
			self::triggerEvent('afterNew', $this);
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
   * Get id of record
   * 
   * @return string the id of the Mongo document
   */
  public function getID() {
    return $this->attributes['_id'];
  }
  
  /**
   * Set id of record
   * 
   * @param string $id new id for the Mongo document
   */
  public function setID($id) {
    $this->attributes['_id'] = $id;
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
	  self::triggerEvent('beforeValidation', $this);
    
    // Check if is valid
		$is_valid = $this->isValid();
    
    // Trigger after validation event
		self::triggerEvent('afterValidation', $this);
    
		return $is_valid;
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
		self::triggerEvent('beforeSave', $this);
    
    // Save the object to the mongo collection
		$collection = self::mongoGetCollection();
		$collection->save($this->attributes);
    
    // Trigger after save new event
    if($this->new) {
      self::triggerEvent('afterNewSave', $this);
    }

    // No longer a new object
		$this->new = FALSE;
    
    // Trigger after save event
		self::triggerEvent('afterSave', $this);

		return TRUE;
	}

  /**
   * Remove 
   * 
   * Removes the object from the Mongo Record
   */
  public function remove() {
    // Trigger before remove event
    self::triggerEvent('beforeRemove', $this);

    // If not a new object then remove it from the mongo collection
    if(!$this->new) {
      $collection = self::mongoGetCollection();
      $collection->remove(array('_id' => $this->attributes['_id']));
    }
    
    // Trigger after remove event
    self::triggerEvent('afterRemove', $this);
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
    self::triggerEvent('beforeDestroy', $this);
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
		$className = get_called_class();
		$methods = get_class_methods($className);
	
		foreach ($methods as $method) {
			if (strcasecmp(substr($method, 0, 9), 'validates') === 0) {
			  $attribute = $this->getter(strtolower(substr($method, 9)));
				if($this->{$method}($attribute) !== TRUE) {
					return FALSE;
				}
        var_dump($this->{$method}($attribute));
			}
		}

		return TRUE; 
	}
  
  /**
   * Register event
   */
  public static function registerEvent($event, $callback) {
    if(!is_callable($callback)) {
      throw new BaseMongoRecordException('Callback must be callable');
    }
    
    if(empty(self::$event_callbacks[$event])) self::$event_callbacks[$event] = array();
    self::$event_callbacks[$event][] = $callback;
  }
  
  /**
   * Trigger event
   */
  protected static function triggerEvent($event, &$scope) {
    if(!empty(self::$event_callbacks[$event])) {
      foreach(self::$event_callbacks[$event] as $callback) {
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

	/*----- Core Conventions -----*/
	
	// Moved to CoreMongoRecord
	
}

