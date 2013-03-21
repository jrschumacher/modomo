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

use Modomo\Config;
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

        $searchKeys = array(
            '{{mongo.coll}}'
        );
        $searchVars = array(
            Inflector::getInstance()->camelize($name)
        );

        $collectionClass = str_replace($searchKeys, $searchVars, Config::$collectionClass);
        $documentClass = str_replace($searchKeys, $searchVars, Config::$documentClass);

        $this->_collectionModel = '\\'.Config::$collectionNS.'\\'.$collectionClass;
        if(!class_exists($this->_collectionModel))
        {
            throw new \RuntimeException($this->_collectionModel.' was not found. Must have a collection model.');
        }

        $this->_documentModel = '\\'.Config::$documentNS.'\\'.$documentClass;
        if(!class_exists($this->_documentModel))
        {
            $this->_documentModel = false;
        }
    }

    /**
     * Get collection model
     */
    public function &getCollection($cursor = null) {
        $coll = new $this->_collectionModel($this, $cursor);
        return $coll;
    }

    /**
     * Get document model
     */
    public function &getDocument($doc, $cursor = null) {
        if(empty($this->_documentModel))
        {
            return $doc;
        }
        else {
            $doc = new $this->_documentModel($doc, $this->getCollection(), $cursor);
            return $doc;
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
    public function &find($query = array(), $fields = array()) {
        $res = null;
        $docs = $this->_collection->find($query, $fields);

        if(!empty($docs))
        {
            $res = new MongoCursor($docs, $this->getCollection($docs));
        }
        return $res;
    }

    /**
     * Querys this collection, returning a single element
     */
    public function &findOne($query = array(), $fields = array()) {
        $res = null;
        $doc = $this->_collection->findOne($query, $fields);

        if(!empty($doc))
        {
            $res = $this->getDocument($doc);
        }
        return $res;
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