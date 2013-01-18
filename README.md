# Modomo (mŏ-dŏ-mŏ)

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

_Coming soon_

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

```
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
## License

Modomo is licensed under the MIT license.