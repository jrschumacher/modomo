<?php

namespace Modomo;

class Subdocument {
  
  protected $_doc = array();

  public function __construct($subdoc) {
    foreach($subdoc as $key => $val) {
      $method = 'set'.ucfirst($key);
      if(method_exists($this, $method)) {
        $this->{$method}($val);
      }
    }
  }

}