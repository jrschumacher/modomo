# THIS REPO IS NO LONGER MAINTAINED. PLEASE USE https://github.com/purekid/mongodm

# Modomo (mŏ-dŏ-mŏ)

[![Latest Stable Version](https://poser.pugx.org/jrschumacher/modomo/version.png)](https://packagist.org/packages/jrschumacher/modomo)
[![Latest Unstable Version](https://poser.pugx.org/jrschumacher/modomo/v/unstable.png)](https://packagist.org/packages/jrschumacher/modomo)

Modomo is a lightweight, event based PHP MongoDB ODM

Designed for the benefits of ODMs (getters and setters, validation and helpers) while maintaining a quick development and low entry level.
All the while proxying MongoDB Core PHP classes for direct access to the MongoDB driver; no custom routines here.

## Features

* Basic ODM features
* Simple document classes
* Simple collection classes
* Validations
* Events and callbacks
* Direct access to MongoDB driver

## Requirements

* PHP 5.3+
* MongoDB Driver

## Installation

### Manual

Extract the source files into a directory in your application library path. Either autoload or require all classes.

### Composer

To add via Composer using Packagist[[jrschumacher/modomo](https://packagist.org/packages/jrschumacher/modomo)] add to your composer.json

```json
{
    "require": {
        "jrschumacher/modomo": "0.6.*"
    }
}
```

## Usage

Using Modomo is very simple. As a basic rule of thumb, if you use the Modomo\MongoClient() everything else will fall in place.
Yet it isn't limited to that, at any point you can turn a Mongo Core Class object into a Modomo object.

### Basic

Using MongoDM is as simple as declaring classes that are extensions of the base ODM class and specifying a namespace.

```php
<?php
    namespace Collections;

    class Person {}
?>
```

```php
<?php
    namespace Documents;

    class Person {}
?>
```

```php
<?php
    use Modomo\MongoClient;
    use Documents\Person;

    $m = new MongoClient();
    $db = $m->test;
    $coll = $db->person;

    $bob = new Person(array(), $coll);
    $bob->name = "Bob";
    $bob->save();

    $people = $coll->find();
    $bob = $people->getNext();
    $bob->getDoc; // array('name' => 'Bob', '_id' => array('$id' => '12345....'));
?>
```

### Configuration

Modomo supports some configuration for storing your collections and documents. This can simply be changed via `Modomo\Config` class which has some static variables to help you out.

_Warning: Due to it's dynamic nature it will change future states._

```php
<?php
    // Assuming MongoDB collection is "people"

    // For collections
    Modomo\Config::$collectionNS;                                   // \Collections\People.php
    Modomo\Config::$collectionNS = 'App\\Collection';               // \App\Collection\People.php
    Modomo\Config::$collectionClass = '{{mongo.coll}}Collection';   // \App\Collection\PeopleCollection.php

    // For documents
    Modomo\Config::$documentNS;                                     // \Documents\People.php
    Modomo\Config::$documentNS = 'App\\Document';                   // \App\Document\People.php
    Modomo\Config::$documentClass = '{{mongo.coll}}Document';       // \App\Document\PeopleDocument.php
?>
```

#### Namespaces

The namespace for the collections or documents may be changed via the `$collecionNS` and `$documentNS` variables. By default they resolve to `\Collections` and '\Documents' respectively.

_Note: use a double slash `\\` when implementing a sub namespace_

```php
<?php
    // To change the name space to \XYZ
    Modomo\Config::$collectionNS = 'XYZ';
    // To change the namespace to \XYZ\ABC
    Modomo\Config::$collectionNS = 'XYZ\\ABC';
?>
```

#### Class Names

The class names for collections and documents may be changed via the `$collectionClass` and `$documentClass` variables. By default they resolve to the name of the MongoDB collection in `StudlyCaps` (see [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md))

A string replace is executed to provide more flexibility with your class names. Following is a list of supported patterns:

```php
<?php
    // To change the class name to XYZ (not a good idea)
    Modomo\Config::$collectionClass = 'XYZ';
    // To change the class name to AwesomePeopleCollection
    Modomo\Config::$collectionClass = 'Awesome{{mongo.coll}}Collection';
?>
```

```
 Pattern                Description
-------------------------------------------------------------------------
{{mongo.coll}}          Replaced with the collection name StudlyCapped
```

***Other replacements will be added upon request and discussion***

_Notes:_

- `mongo` is reserved for MongoDB related replacements


### Document

### CRUD Methods

### Other Methods

### Validators

### Events / Callbacks

A number of events exist throughout Modomo. You can hook into these events by registering your callable method with your collection.

#### Event Hooks

- beforeCreate
- beforeCreateNew
- afterCreate
- afterCreateNew
- beforeSave
- beforeSaveNew
- afterSave
- afterSaveNew
- beforeValidation
- afterValidation
- beforeDestroy

In a new, save, destroy cycle, the validations are called in the following order:

`beforeCreateNew -> afterCreateNew -> beforeValidation -> afterValidation -> beforeSaveNew -> afterSaveNew -> beforeDestroy`

```php
<?php
    namespace Collections;

    class Person {}

    $beforeSaveCallback = function() {};

    Person::registerEvent('beforeSave', $beforeSaveCallback, array('param1', 'param2'));
?>
```
