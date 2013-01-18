<?php

/*
 * This file is part of the Modomo library.
 *
 * (c) Ryan Hamilton-Schumacher <ryan@generouscode.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Modomo\Adapters;

use Modomo\Helpers\Inflector;

class MongoCollection
{
    /**
     * 
     */
    protected static $_collections = array();

    /**
     * Mongo collection object
     */
    protected $_collection;

    /**
     * 
     */
    protected $_collectionModel;

    /**
     * 
     */
    protected $_documentModel;

    /**
     * Creates a new collection
     */
    public function __construct()
    {
        $arguments = func_get_args();

        if(!empty($arguments) && $arguments[0] instanceof \MongoCollection)
        {
            $this->_collection = $arguments[0];
        }
        else
        {
            $class = new \ReflectionClass('\MongoCollection');
            $this->_collection = $class->newInstanceArgs($arguments);
        }

        $name = Inflector::getInstance()->camelize($this->_collection->getName());

        $this->_collectionModel = '\\Collections\\'.Inflector::getInstance()->camelize($name);
        if(!class_exists($this->_collectionModel))
        {
            throw new \RuntimeException($this->_collectionModel.' was not found. Must have a collection model.');
        }

        $this->_documentModel = '\\Documents\\'.Inflector::getInstance()->camelize($name);
        if(!class_exists($this->_documentModel))
        {
            $this->_documentModel = false;
        }
    }

    /**
     * Get collection model
     */
    public function getCollection($cursor = null) {
        return new $this->_collectionModel($this, $cursor);
    }

    /**
     * Get document model
     */
    public function getDocument($doc, $cursor = null) {
        if(empty($this->_documentModel))
        {
            return $doc;
        }
        else {
            return new $this->_documentModel($doc, $this->getCollection(), $cursor);
        }
    }

    /**
     * Get a collection
     */
    public function __get($name)
    {
        $collection = $this->_collection->__get($name);
        return new MongoCollection($collection);
    }

    /**
     * Querys this collection, returning a MongoCursor for the result set
     */
    public function find($query = array(), $fields = array()) {
        $cursor = $this->_collection->find($query, $fields);
        return new MongoCursor($cursor, $this->getCollection($cursor));
    }

    /**
     * Querys this collection, returning a single element
     */
    public function findOne($query = array(), $fields = array()) {
        $doc = $this->_collection->find($query, $fields);
        return $this->getDocument($doc);
    }

    /**** Proxied ****/

    public function __call($name, $arguments)
    {
        $return = call_user_func_array(array($this->_collection, $name), $arguments);
        return $return;
    }

    public function __staticCall($name, $arguments)
    {
        $return = call_user_func_array(array($this->_collection, $name), $arguments);
        return $return;
    }
}