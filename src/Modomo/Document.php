<?php

/*
 * This file is part of the Modomo library.
 *
 * (c) Ryan Hamilton-Schumacher <ryan@generouscode.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Modomo;

use Modomo\Helpers\Inflector;

abstract class Document
{
    /**
     * Debug
     */
    public static $_debug = false;

    /**
     * Document
     */
    protected $_doc = array();

    /**
     * Collection
     */
    protected $_collection;

    /**
     * New model
     */
    protected $_new;

    /**
     * Dirty model
     */
    protected $_dirty;

    /**
     * Strict schema
     * 
     * Require a strict schema
     */
    protected $_strictSchema = false;

    /**
     * Model contructor
     * 
     * Build the model
     */
    public function __construct($doc, $collection) 
    {
        // Validate doc
        $this->_doc = $doc;
        if(!is_array($doc)) {
            throw new \RuntimeException('$data must be array.');
        }

        // Validate collection
        $this->_collection = $collection;
        if($collection instanceof \MongoCollection)
        {
            $collection = new MongoCollection($collection);
            $this->_collection = $collection->getCollection();
        }
        else if($collection instanceof Adapters\MongoCollection)
        {
            $this->_collection = $collection->getCollection();
        }
        else if(!$collection instanceof Collection)
        {
            throw new \RuntimeException('$collection must be an instance of Modomo\\Collection or Modomo\\Adapters\\MongoCollection or MongoCollection');
        }

        // New model
        $this->_new = true;
        if(isset($doc['_id']) && $doc['_id'] instanceof \MongoId)
        {
            $this->_new = false;
            $this->_collection->triggerEvent('onCreateNew', $this);
        }
        else
        {
            $this->_collection->triggerEvent('onCreate', $this);
        }
    }

    /**
     * 
     */
    public function &_getCollection() {
        return $this->_collection;
    }

    /**
     * Save
     */
    public function &save($options = array(), $force = false) 
    {
        $class = get_called_class();

        // Don't save if !$dirty and !$new && !$forced
        if(!$this->_dirty && !$this->_new && !$force)
        {
            if(self::$_debug === true)
            {
                trigger_error('Modomo\Model::save() not saved when !$dirty. Use Modomo\Model::save($options, true) to override.', E_USER_WARNING);
            }
            return $this;
        }

        if($this->_new)
        {
            $this->_collection->triggerEvent('beforeSaveNew', $this);

            $this->_collection->insert($this->_doc, $options);
            $this->_new = false;

            $this->_collection->triggerEvent('afterSaveNew', $this);
        }
        else
        {
            $this->_collection->triggerEvent('beforeSave', $this);

            $this->_collection->save($this->_doc, $options);

            $this->_collection->triggerEvent('afterSave', $this);
        }

        return $this;
    }

    /**
     * Remove 
     * 
     * Removes the object from the Mongo Record
     */
    public function &remove()
    {
        if($this->_new)
        {
            if(self::$_debug === true)
            {
                trigger_error('Modomo\Model::remove() not saved when $new.', E_USER_WARNING);
            }

            return $this;
        }

        $this->_collection->triggerEvent('beforeRemove', $this);

        $this->_collection->remove(array('_id' => $this->_doc['_id']));

        $this->_collection->triggerEvent('afterRemove', $this);
      
        return $this;
    }

    /**
     * Setter
     */
    protected function _setter($name, $value = null, $force = false) 
    {
        $camelName = Inflector::getInstance()->camelize($name);

        if(method_exists($this, 'validates'.$camelName))
        {
            if($error = $this->{'validates'.$camelName}($value) !== true)
            {
                return $error;
            }
        }

        if(method_exists($this, 'set'.$camelName)) {
            $this->{'set'.$camelName}($value);
        }
        else {
            if($this->_strictSchema === true && !isset($this->_doc[$name]))
            {
                trigger_error('Invalid schema.', E_USER_WARNING);
                return false;
            }
        
            $this->_doc[$name] = $value;
        }

        // Model is now dirty
        $this->_dirty = true;

        return true;
    }
    
    /**
     * Getter
     */
    protected function _getter($name)
    {
        if(!isset($this->_doc[$name])) 
        {
            return null;
        }
          
        $method = 'get'.Inflector::getInstance()->camelize($name);
        if(method_exists($this, $method))
        {
            return $this->{$method}();
        }
        return $this->_doc[$name];    
    }

    /**
     * Validate the doc
     * 
     * Runs methods 
     * 
     * @return bool is valid 
     */
    protected function _isValid()
    {
        $class = get_called_class();
        $methods = get_class_methods($class);
    
        foreach($methods as $method) {
            if(strcasecmp(substr($method, 0, 9), 'validates') === 0)
            {
                $value = $this->_getter(strtolower(substr($method, 9)));
                if($error = $this->{$method}($value) !== true)
                {
                    return $error;
                }
            }
        }

        return true; 
    }
    
    /**
     * Get document
     */
    public function getDoc($options = array()) 
    {
        $opts = array_merge(array(), $options);
        $doc = $this->_doc;

        if(!empty($opts))
        {
            array_walk_recursive($doc, function($item, $key)
            {
                // _id to id
                if($key === '_id' && isset($opts['_id']) && $opts['_id'] === 'id') 
                {
                    $doc['id'] = $doc['_id'];
                    unset($doc['_id']); 
                }

                // MongoId to string
                if($key instanceof \MongoId && isset($opts['MongoId']) && $opts['MongoId'] === 'string')
                {
                    $doc[$key] = $doc[$key]->__toString();
                }
            });
        }

        return $doc;
    }

    /**
     * 
     */
    public function __set($name, $value)
    {
        $this->_setter($name, $value);
    }

    /**
     * 
     */
    public function __get($name)
    {
        return $this->_getter($name);
    }
    
    /**
     * To string
     */
    public function __toString()
    {
        return json_encode($this->getDoc());
    }
}