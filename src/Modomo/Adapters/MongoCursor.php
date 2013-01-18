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

use Modomo\Collection;
use Modomo\Helpers\Inflector;

class MongoCursor
{
    /**
     * Mongo cursor object
     */
    protected $_cursor;

    /**
     * Mongo document collection model
     */
    protected $_collection;

    /**
     * Creates a new cursor
     */
    public function __construct()
    {
        $arguments = func_get_args();

        if(!empty($arguments) && $arguments[0] instanceof \MongoCursor)
        {
            $this->_cursor = $arguments[0];
            $info = $this->_cursor->info();
            list($dbName, $collectionName) = explode('.', $info['ns'], 2);

            if(!isset($arguments[1]) || !$arguments[1] instanceof Collection)
            {
                throw new \RuntimeException('Modomo\\MongoCursor::__construct() expects parameter 2 to be of type Modomo\\Adapters\\MongoCollection');
            }

            $this->_collection = $arguments[1];
        }
        else
        {
            $class = new \ReflectionClass('\MongoCursor');
            $this->_cursor = $class->newInstanceArgs($arguments);
            $info = $this->_cursor->info();
            list($dbName, $collectionName) = explode('.', $info['ns'], 2);

            $conn = new MongoClient($arguments[0]);
            $this->_collection = $conn->selectCollection($dbName, $collectionName);
        }
    }

    /**
     * Proxy current and returns the current element as document model
     * 
     * Applies document model if exists
     */
    public function current()
    {
        $doc = $this->_cursor->current();
        if(empty($doc)) {
            return null;
        }
        return $this->_collection->getCollection($this)->getDocument($doc);
    }

    /**
     * Proxy getNext and return modified current
     */
    public function getNext()
    {
        $this->_cursor->next();
        return $this->current();
    }

    /**** Proxied ****/

    public function __call($name, $arguments)
    {
        $return = call_user_func_array(array($this->_cursor, $name), $arguments);
        if(is_object($return) && get_class($return) === 'MongoCursor')
        {
            return $this;
        }
        return $return;
    }

    public function __staticCall($name, $arguments)
    {
        $return = call_user_func_array(array($this->_cursor, $name), $arguments);
        if(is_object($return) && get_class($return) === 'MongoCursor')
        {
            return $this;
        }
        return $return;
    }

}