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

class MongoDB extends \MongoDB
{
    /**
     * Mongo database connection object
     */
    protected $_dbconn;

    /**
     * Creates a new database
     */
    public function __construct()
    {
        $arguments = func_get_args();

        if(!empty($arguments) && $arguments[0] instanceof \MongoDB)
        {
            $this->_dbconn = $arguments[0];
        } else
        {
            $class = new \ReflectionClass('\MongoDB');
            $this->_dbconn = $class->newInstanceArgs($arguments);
        }
    }

    /**
     * Get a collection
     */
    public function __get($name)
    {
        $collection = $this->_dbconn->__get($name);
        return new MongoCollection($collection);
    }

    /**
     * Gets a collection
     */
    public function selectCollection($name)
    {
        $collection = $this->_dbconn->selectCollection($name);
        return new MongoCollection($collection);
    }

    /**** Proxied ****/

    public function __call($name, $arguments)
    {
        $return = call_user_func_array(array($this->_dbconn, $name), $arguments);
        if(is_object($return) && get_class($return) === 'MongoClient')
        {
            return $this;
        }
        return $return;
    }

    public function __staticCall($name, $arguments)
    {
        $return = call_user_func_array(array($this->_dbconn, $name), $arguments);
        if(is_object($return) && get_class($return) === 'MongoClient')
        {
            return $this;
        }
        return $return;
    }
}