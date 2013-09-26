[![Build Status](https://www.travis-ci.org/fkooman/php-lib-rest.png?branch=master)](https://www.travis-ci.org/fkooman/php-lib-rest)

# Introduction
Library written in PHP to make it easy to develop REST applications. 

# Features
The library has the following features:
* Wrapper HTTP request and response class to make it very easy to test your
  applications
* RESTful router support

Furthermore extensive tests are available written in PHPUnit.

# Installation
You can use this library through [Composer](http://getcomposer.org/) by 
requiring `fkooman/php-lib-rest`. 

# Tests
You can run the PHPUnit tests if PHPUnit is installed:

    $ phpunit tests/

You need to run Composer **FIRST** in order to be able to run the tests:

    $ php /path/to/composer.phar install
        
# Example
A simple sample application can be found in the `examples/` directory. 
Please check there to see how to use this library. The example should work
"as is" when placed in a directory reachable through a web server.

# API
The API documenation can be generated using
[Sami](http://sami.sensiolabs.org/), Sami is part of the `require-dev` section
in the Composer file.

    $ php vendor/bin/sami.php update doc/php-lib-rest.php

This will output HTML in the `build/` directory.

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
