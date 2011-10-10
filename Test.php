<?php
  /**
   * Mongo Record, a simple Mongo ORM for PHP with ActiveRecord-like features.
   * 
   * @author     Ryan Schumacher <https://github.com/jrschumacher/>
   * @author     Lu Wang <https://github.com/lunaru/>
   * @license    See LICENSE
   * @version    1.5
   */

  /**
   * Test, a test script which validates Mongo Record working
   *
   * Does very little right now...
   */
  require_once('Loader.php');

  use MongoRecord as MR;

  MR\CoreMongoRecord::mongoSetConnection();
  MR\CoreMongoRecord::mongoSetDatabase('test');

  $test = new MR\MongoRecord('test');
  $test->a = 1;
  $test->save();

  print "<pre>";
  var_dump(MR\MongoRecord::find('test'));