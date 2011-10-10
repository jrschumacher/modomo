<?php
/**
 * Mongo Record, anonymous Mongo Record when an entire class isn't needed
 * 
 * Used when only an anonymous Mongo Record is needed
 * 
 * @author Lu Wang <https://github.com/lunaru/>
 * @version 1.0.1
 * @package MongoRecord
 */
 
 final class MongoRecord extends BaseMongoRecord {
   
   protected $__meta;
   
   public static function find($collection, $query = array(), $options = array()) {
     return parent::find($query, $options, $collection);
   }
   
   public static function findOne($collection, $query = array(), $options = array()) {
     return parent::findOne($query, $options, $collection);
   }
   
   public static function count($collection, $query = array()) {
     return parent::count($query, $collection);
   }
   
   protected static function instantiate($collection, $document) {
     return parent::instantiate($document, $collection);
   }
   
   protected static function registerEvent($collection, $event, $callback) {
     return parent::registerEvent($event, $callback, $collection);
   }
   
   protected static function triggerEvent($collection, &$scope, $event) {
     return parent::registerEvent($scope, $event, $collection);
   }
   
 }
