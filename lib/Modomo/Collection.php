<?php

namespace Modomo;

abstract class Collection
{
    /**
     * 
     */
    protected $_collection;

    /**
     * Cursor container
     * 
     * Give access to the cursor which the model came from
     */
    protected $_cursor;

    /**
     * 
     */
    public function __construct($collection, $cursor = false)
    {
        $this->_collection = $collection;
    }

    /**
     * 
     */
    public function &getCollection()
    {
        return $this->_collection;
    }

    /**
     * Get Cursor
     * 
     * @return MongoCursor|null returns the cursor associated to model
     */
    public function &getCursor() 
    {
        return $this->_cursor;
    }

    /**
     * Register event
     * 
     * Define callbacks to execute per event
     * 
     * @param $event string name of event
     * @param $callback function function to execute
     */
    public function registerEvent($event, $callback)
    {
        $class = get_called_class();

        // Callable
        if(!is_callable($callback))
        {
            throw new BaseMongoRecordException('Callback must be callable');
        }

        // Define for first time
        if(empty($this->_events[$event]))
        {
            $this->_events[$event] = array();
        }

        $this->_events[$event][] = $callback;
    }
    
    /**
     * Trigger event
     * 
     * @param $event string name of event
     * @param $scope Model the current scope
     */
    public function triggerEvent($event, &$scope)
    {
        if(isset($this->_events[$event]) && is_array($this->_events[$event]))
        {
            foreach($this->_events[$event] as $callback)
            {
                if(is_callable($callback))
                {
                    call_user_func($callback, $scope);
                }
            }
        }
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