<?php

namespace MongoRecord;

class MongoRecordHelper {
  
  static function intersectArray($array, $default) {
    return array_intersect_key($array, $default);
  }
  
  static function mergeArray($defualt, $array) {
    return array_merge($defualt, $array);
  }
  
}
