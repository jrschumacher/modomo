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

class MongoClient
{
    /**
     * Mongo connection object
     */
    protected $_conn;

    /**
     * Creates a new database connection object
     */
    public function __construct()
    {
        $arguments = func_get_args();

        if(!empty($arguments) && $arguments[0] instanceof \MongoClient)
        {
            $this->_conn = $arguments[0];
        }
        else 
        {
            $class = new \ReflectionClass('\MongoClient');
            $this->_conn = $class->newInstanceArgs($arguments);
        }
    }

    /**
     * Gets a database
     */
    public function __get($name)
    {
        $db = $this->_conn->__get($name);
        return new MongoDB($db);
    }

    /**
     * Gets a database
     */
    public function selectDB($name)
    {
        $db = $this->_conn->selectDB($name);
        return new MongoDB($db);
    }

    /**
     * Gets a collection
     */
    public function selectCollection($db, $collection) {
        $db = $this->_conn->selectDB($db);
        $db = new MongoDB($db);
        return $db->selectCollection($collection);
    }

    /**** Proxied ****/

    public function __call($name, $arguments)
    {
        $return = call_user_func_array(array($this->_conn, $name), $arguments);
        if(is_object($return) && get_class($return) === 'MongoClient')
        {
            return $this;
        }
        return $return;
    }

    public function __staticCall($name, $arguments)
    {
        $return = call_user_func_array(array($this->_conn, $name), $arguments);
        if(is_object($return) && get_class($return) === 'MongoClient')
        {
            return $this;
        }
        return $return;
    }

}