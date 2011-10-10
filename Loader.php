<?php
  /**
   * Mongo Record, a simple Mongo ORM for PHP with ActiveRecord-like features.
   * 
   * @author     Ryan Schumacher <https://github.com/jrschumacher/>
   * @author     Lu Wang <https://github.com/lunaru/>
   * @license    See LICENSE
   * @version    1.5
   */
  
  $dir = dirname(__FILE__) . '/';
  
  /* Require Inflector */
  require_once($dir . 'lib/Inflector.php');
  
  /* Require Mongo Record Exception */
  require_once($dir . 'lib/MongoRecordExceptions.php');
   
  /* Require Core Mongo Record */
  require_once($dir . 'lib/CoreMongoRecord.php');
  
  /* Require Base Mongo Record */
  require_once($dir . 'lib/BaseMongoRecord.php');
  
  /* Require Mongo Record */
  require_once($dir . 'lib/MongoRecord.php');
