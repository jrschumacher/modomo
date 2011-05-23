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
  * Require Mongo Record
  */
require_once('MongoRecord.php');
/**
 * Require Inflector
 */
require_once('Inflector.php');

/**
 * Base Mongo Record, main abstraction layer for the Mongo Record
 * 
 * @package MongoRecord
 */
abstract class BaseMongoRecord 
  implements MongoRecord {
  
  /**
   * @uses stores the attributes for the record
   * @var array
   */
	protected $attributes = array();
  
  /**
   * @var array
   */
	protected $errors = array();
  
  /**
   * @uses defined upon creation of record
   * @var BOOL
   */
	private $new = TRUE;
  
  public static $database = null;
  public static $connection = null;
  public static $findTimeout = 20000;

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
    $this->attributes = $attributes;
    
		$this->errors = array();

		if($new) {
			$this->afterNew();
    }
	}

  /**
   * Validate this object
   * 
   * Runs through the validation routines of each attribute as well as triggering
   * the validation hooks.
   * 
   * @return bool $retval
   */
	public function validate() {
	  // Before validation hook
		$this->beforeValidation();
    
    // Check if is valid
		$retval = $this->isValid();
    
    // After validation hook
		$this->afterValidation();
    
		return $retval;
	}

  /**
   * Save the object to the Mongo Record
   * 
   * Runs through the save procedure: validate the object, trigger hooks, save
   * record to Mongo collection
   * 
   * @return bool whether the save was successful
   */
	public function save() {
	  // Validate the object
		if(!$this->validate()) {
			return FALSE;
    }

    // Before save hook
		$this->beforeSave();
    
    // Save the object to the mongo collection
		$collection = self::getCollection();
		$collection->save($this->attributes);

    // No longer a new object
		$this->new = FALSE;
    
    // After save hook
		$this->afterSave();

		return TRUE;
	}

  /**
   * Destroy, remove record from Mongo collection
   */
	public function destroy() {
	  $this->beforeDestroy();

    if(!$this->new) {
      $collection = self::getCollection();
      $collection->remove(array('_id' => $this->attributes['_id']));
    }
	}

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
		$collection = self::getCollection();
		$documents = $collection->find($query);
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
		$collection = self::getCollection();
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
			$className = get_called_class();
			return new $className($document, FALSE);
		}
    
		return NULL;
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
   * __call, used to do getters or setters for the record attributes
   * 
   * @param string $method the method called
   * @param array $attributes the attributes of the called method
   * @return mixed if a getter the
   */
	public function __call($method, $arguments) {
    // Is this a getter or setter
		$prefix = strtolower(substr($method, 0, 3));
		if($prefix != 'get' && $prefix != 'set') {
		  return;
    }

		// What is the get/set class attribute
		$inflector = Inflector::getInstance();
		$property = $inflector->underscore(substr($method, 3));

    // Did not match a get/set call
		if(empty($prefix) || empty($property)) {
			throw New Exception("Calling a non get/set method that does not exist: $method");
		}

		// Getter method
		if ($prefix == "get" && array_key_exists($property, $this->attributes)) {
		  if(method_exists($this, $method)) return $this->{$method}();
			return $this->attributes[$property];
		}
		else if ($prefix == "get") {
			return NULL;
		}

		// Setter method
		if ($prefix == "set" && array_key_exists(0, $arguments)) {
		  if(method_exists($this, $method)) $this->{$method}($arguments[0]);
      else $this->attributes[$property] = $arguments[0];
			return $this;
		}
		else {
			throw new Exception("Calling a get/set method that does not exist: $property");
		}
	}

	/*----- Hooks -----*/
	public function beforeSave() {}
	public function afterSave() {}
	public function beforeValidation() {}
	public function afterValidation() {}
	public function beforeDestroy() {}
	public function beforeRemove() {}
  public function afterRemove() {}
	public function afterNew() {}

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
				$propertyCall = 'get' . substr($method, 9);
				if(!$className::$method($this->$propertyCall())) {
					return FALSE;
				}
			}
		}

		return TRUE; 
	}

	/*----- Core Conventions -----*/

  /**
   * Sets the find timeout of the Mongo connection
   * 
   * @param int $timeout
   */
  public static function setFindTimeout($timeout) {
    $className = get_called_class();
    $className::$findTimeout = $timeout;
  }
	
	/**
   * Get the Mongo collection
   * 
   * @return MongoCollection
   */
	protected static function getCollection() {
		$className = get_called_class(); 
		$inflector = Inflector::getInstance();
		$collection_name = $inflector->tableize($className);

		if ($className::$database == NULL) {
			throw new Exception("BaseMongoRecord::database must be initialized to a proper database string");
    }

		if ($className::$connection == NULL) {
			throw new Exception("BaseMongoRecord::connection must be initialized to a valid Mongo object");
    }
		
		if (!($className::$connection->connected)) {
			$className::$connection->connect();
    }

		return $className::$connection->selectCollection($className::$database, $collection_name);
	}
}

