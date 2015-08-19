# CodeDump.io-API-client-PHP
A PHP Client for the CodeDump.io API

## What is CodeDump.io?
CodeDump.io is a website where programmers can dump any code or configuration they like in
any language they desire, in order for other programmers to benefit from it.

## What does this client do?
CodeDump.io-API-client-PHP provides everything you need to communicate with the
[CodeDump.io](http://codecump.io) API:

* upload code from from a string of a source file
* request available languages
* request available access types

## How to install the client?
The client can easily be installed using [Composer](http://getcomposer.org):

    "require": { "rkirkels/codedump_io": "1.0.*" }
    
## Prerequisites
In order to use the this client, you need:

* A (free) account with CodeDump.io to obtain your API key and secret.
* PHP-cURL modules installed.

## How to use this client in your code?
The client can be instantiate as a new object:

    $client = new \codedump_io\CodeDumpClient(<apiKey>,<apiSecret>);

Or as a singleton:

    $client = \codedump_io\CodeDumpClient::getInstance(<apiKey>,<apiSecret>);
    
*The API key and secret can also be defined in the in src/CodeDumpClient.php file, so you 
don't have to provide them when instantiating.*

## Example code

    $client = \codedump_io\CodeDumpClient::getInstance(<apiKey>,<apiSecret>);
    
    // Get all available languages:
    $client->command('languages/get');
    $languages = $client->response();
    
    // Get all access types:
    $client->command('access/get');
    $accessTypes = $client->response();
    
    // Post some PHP code:
    $title = 'Some cool title for your code dump';
    $description = 'Some text to describe your piece of code.'; // You can use EOL characters if needed
    $code = '<?php phpinfo(); ?>'; // Your actual code
    $accessType = 'public';
    $language = "PHP";
    
    $linkToCodeDump = $client->addCode($title, $description, $code , $accessType, $language);
    
    // Post code from a file:
    $title = 'Some cool title for your code dump';
    $description = 'Some text to describe your piece of code.'; // You can use EOL characters if needed
    $file = 'file_containing_code.php';
    $accessType = 'public';
    $language = "PHP";
    
    $linkToCodeDump = $client->addCodeFromFile($title, $file, $description, $accessType, $language);