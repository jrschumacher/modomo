<?php

namespace MongoRecord;

class MongoRecordHelper {
  
  static function intersectArray($array, $keys) {
    return array_intersect_key($array, array_fill_keys($keys, 1));
  }
  
}
