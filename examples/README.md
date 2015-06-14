# Introduction
This application will show how to use the REST service routing capabilies of 
this framework

# Tests
You can use cURL to test this code, assuming `php-lib-rest` is available 
through `http://localhost/php-lib-rest/examples/rest.php`. Modify your URL
accordingly.

Example with successful result:

    $ curl -i http://localhost/php-lib-rest/examples/rest.php/hello/world
    HTTP/1.1 200 OK
    Date: Thu, 11 Jun 2015 17:05:12 GMT
    Server: Apache/2.4.12 (Fedora) OpenSSL/1.0.1k-fips PHP/5.6.9
    X-Powered-By: PHP/5.6.9
    Content-Length: 39
    Content-Type: application/json

    {"type":"GET","response":"hello world"}

Example where URL is missing:

    $ curl -i http://localhost/php-lib-rest/examples/rest.php/foo
    HTTP/1.1 404 Not Found
    Date: Thu, 11 Jun 2015 17:04:27 GMT
    Server: Apache/2.4.12 (Fedora) OpenSSL/1.0.1k-fips PHP/5.6.9
    X-Powered-By: PHP/5.6.9
    Content-Length: 96
    Content-Type: application/json

    {"error":"url not found","error_description":"\/fkooman\/php-lib-rest\/examples\/rest.php\/foo"}

Example where the request method is not supported:

    $ curl -i -X DELETE http://localhost/php-lib-rest/examples/rest.php/hello/world
    HTTP/1.1 405 Method Not Allowed
    Date: Thu, 11 Jun 2015 17:02:59 GMT
    Server: Apache/2.4.12 (Fedora) OpenSSL/1.0.1k-fips PHP/5.6.9
    X-Powered-By: PHP/5.6.9
    Allow: GET,HEAD,POST
    Content-Length: 39
    Content-Type: application/json

    {"error":"method DELETE not supported"}

Example where a certain request is not acceptable

    $ curl -i -X POST http://localhost/php-lib-rest/examples/rest.php/hello/foo
    HTTP/1.1 400 Bad Request
    Date: Thu, 11 Jun 2015 17:03:53 GMT
    Server: Apache/2.4.12 (Fedora) OpenSSL/1.0.1k-fips PHP/5.6.9
    X-Powered-By: PHP/5.6.9
    Content-Length: 35
    Connection: close
    Content-Type: application/json

    {"error":"you cannot say \"foo!\""}
