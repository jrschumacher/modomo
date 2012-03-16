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
   * Mongo Record, anonymous Mongo Record
   * 
   * Used when only an anonymous Mongo Record is needed and an entire class
   * isn't needed. It is also useful for on the fly creation of MongoRecords
   * similar to Mongo's native ability to create collections on the fly.
   *
   * All methods defined below are used to add the collection name.
   *
   * @package MongoRecord
   */
  
  final class MongoRecord extends BaseMongoRecord {

    public static function find($query = array(), $options = array()) {
      // Hack the abstract
      if(func_num_args() > 0) {
        $args = func_get_args();

        // Set default values
        if(!isset($args[1]) || !is_array($args[1])) {
          $args[1] = array();
        }
        if(!isset($args[2]) || !is_array($args[2])) {
          $args[2] = array();
        }

        if(is_string($args[0])) {
          return parent::find($args[1], $args[2], $args[0]);
        }
      }

      throw new MongoRecordException('Invalid use. MongoRecord::find(string $collection, array $query, array $options);');
    }

    public static function findOne($query = array(), $options = array()) {
      // Hack the abstract
      if(func_num_args() > 0) {
        $args = func_get_args();

        // Set default values
        if(!isset($args[1]) || !is_array($args[1])) {
          $args[1] = array();
        }
        if(!isset($args[2]) || !is_array($args[2])) {
          $args[2] = array();
        }

        if(is_string($args[0])) {
          return parent::findOne($args[1], $args[2], $args[0]);
        }
      }

      throw new MongoRecordException('Invalid use. MongoRecord::findOne(string $collection, array $query, array $options);');
    }

    public static function count($query = array()) {
      // Hack the abstract
      if(func_num_args() > 0) {
        $args = func_get_args();

        // Set default values
        if(!isset($args[1]) || !is_array($args[1])) {
          $args[1] = array();
        }

        if(is_string($args[0])) {
          return parent::count($args[1], $args[0]);
        }
      }

      throw new MongoRecordException('Invalid use. MongoRecord::count(string $collection, array $query);');
    }

    protected static function instantiate($document = array()) {
      // Hack the abstract
      if(func_num_args() > 0) {
        $args = func_get_args();

        // Set default values
        if(!isset($args[1]) || !is_array($args[1])) {
          $args[1] = array();
        }

        if(is_string($args[0])) {
          return parent::instantiate($args[1], $args[0]);
        }
      }

      throw new MongoRecordException('Invalid use. MongoRecord::instantiate(string $collection, array $document);');
    }

    public static function registerEvent($event, $callback) {
      // Hack the abstract
      if(func_num_args() === 3) {
        $args = func_get_args();
        if(is_string($args[0]) && is_string($args[1]) && isset($callback)) {
          return parent::registerEvent($args[1], $arg[2], $args[0]);
        }
      }

      throw new MongoRecordException('Invalid use. MongoRecord::registerEvent(string $collection, string $event, callable $callback);');
    }

    protected static function triggerEvent($event, &$scope) {
      // Hack the abstract
      if(func_num_args() === 3) {
        $args = func_get_args();
        if(is_string($args[0]) && is_string($args[1]) && is_object($args[2])) {
          return parent::triggerEvent($args[1], $arg[2], $args[0]);
        }
      }

      throw new MongoRecordException('Invalid use. MongoRecord::triggerEvent(string $collection, string $event, object $scope);');
    }

    public function __construct($attributes = array(), $new = TRUE) {
      // Hack the abstract
      if(func_num_args() > 0) {
        $args = func_get_args();

        // Set default values
        if(!isset($args[1]) || !is_array($args[1])) {
          $args[1] = array();
        }
        if(!isset($args[2]) || !is_bool($args[2])) {
          $args[2] = TRUE;
        }

        if(is_string($args[0])) {
          parent::__construct($args[1], $args[2], $args[0]);
          return;
        }
      }

      throw new MongoRecordException('Invalid use. new MongoRecord(string $collection, array $attributes, bool $new);');
    }
   
  }
