[![Build Status](https://travis-ci.org/fkooman/php-lib-rest.png?branch=master)](https://travis-ci.org/fkooman/php-lib-rest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fkooman/php-lib-rest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fkooman/php-lib-rest/?branch=master)

# Introduction
Library written in PHP to make it easy to develop web and REST applications. 

# Features
The library has the following features:
* Wrapper HTTP `Request` and `Response` class to make it very easy to test your
  applications
* RESTful router support
* Various plugins available for authentication

Furthermore, extensive tests are available written in PHPUnit.

# Installation
You can use this library through [Composer](http://getcomposer.org/) by 
requiring `fkooman/rest`.

# Tests
You can run the PHPUnit tests if PHPUnit is installed:

    $ phpunit

You need to run Composer **FIRST** in order to be able to run the tests:

    $ php /path/to/composer.phar install
        
# Example
Some sample applications can be found in the `examples/` directory. Please 
check there to see how to use this library. The example should work "as is" 
when placed in a directory reachable through a web server.

# Web Server Configuration
It is recommended to structure your application in the following way:

    /var/www/app/
        composer.json
        composer.lock
        src/
            fkooman/App/
                AppService.php
        web/
            index.php
            robots.txt
            favicon.ico
            css/
                style.css

Now you can use the following snippet to make it work in a 'folder' in your
web server. For example on CentOS you can put the following in 
`/etc/httpd/conf.d/app.conf`:

    Alias /app /var/www/app/web

    <Directory "/var/www/app/web">
        RewriteEngine On
        RewriteBase /app

        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php/$1 [QSA,L]

        SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
    </Directory>

This will make everything work perfectly using Apache and `mod_php`. If you
want to use for example PHP-FPM you can use `ProxyPassMatch` in your 
`VirtualHost` section instead:

    SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1

    ProxyPassMatch /css !
    ProxyPassMatch /robots.txt !
    ProxyPassMatch /favicon.ico !
    ProxyPassMatch .* fcgi://127.0.0.1:9000/var/www/app/web/index.php

# License
Licensed under the Apache License, Version 2.0;

   http://www.apache.org/licenses/LICENSE-2.0
