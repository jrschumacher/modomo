# MongoDM

MongoDM is a lightweight, event based PHP MongoDB ODM

Designed for the benefits of ODMs while maintaining a low entry level and the most basic PHP classes. All the while direct access to the MongoDB driver; no wrapping here.

## Features

* Basic ODM features
* Simple document classes
* Validations
* Events and callbacks
* Direct access to MongoDB driver

## Requirements

* PHP 5.3+
* MongoDB Driver

## Installation

### Manual

Extract the source files into a directory in your PHP library path.

### Composer

## Usage

### Basic

Using MongoDM is as simple as declaring classes that are extensions of the base ODM class and specifying a namespace.

```php
  <?php // /app/documents/person.php
    namespace MyApplication/Documents

    class Person extends MongoDM {}
  ?>
```

```
  <?php // /app/app.php
    // initialize connection and database name
    MongoRecord::init(
        "connection": "mongodb://localhost:21070",
        "database": "myApp"
    );

    $bob = new Person();
    $bob->name = "Bob";
    $bob->save();
  ?>
```

### Attributes

### CRUD Methods

### Other Methods

### Validators

### Events / Callbacks

A number of events exist throughout MongoDM. You can hook into these events by registering your callable method with the class.

#### Event Hooks

- beforeSave
- afterSave
- beforeValidation
- afterValidation
- beforeDestroy
- afterNew

In a new, save, destroy cycle, the validations are called in the following order:

`afterNew -> beforeValidation -> afterValidation -> beforeSave -> afterSave -> beforeDestroy`

``` php
class Person extends MongoRecord {}

$beforeSaveCallback = function() {};

Person::registerEvent('beforeSave', $beforeSaveCallback, array('param1', 'param2'));
```
