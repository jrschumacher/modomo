<?php

namespace Modomo;

class Config
{
    
    public static $collectionNS = 'Collections';
    public static $documentNS = 'Documents';

    /**
     * Set the class name of the collections and documents
     *
     * Basic use is to just set the name, but this is not enough when
     * working with more than one collection/document.
     *
     * Variables:
     *
     *     {{mongo.coll}} - MongoDB collection name
     *
     */
    public static $collectionClass = '{{mongo.coll}}';
    public static $documentClass = '{{mongo.coll}}';

}