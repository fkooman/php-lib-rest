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
    Date: Fri, 10 Oct 2014 14:43:32 GMT
    Server: Apache/2.4.10 (Fedora) OpenSSL/1.0.1e-fips PHP/5.5.17
    X-Powered-By: PHP/5.5.17
    Content-Length: 39
    Content-Type: application/json

    {"type":"GET","response":"hello world"}

Example where URL is missing:

    $ curl -i http://localhost/php-lib-rest/examples/rest.php/foo
    HTTP/1.1 404 Not Found
    Date: Fri, 10 Oct 2014 14:44:01 GMT
    Server: Apache/2.4.10 (Fedora) OpenSSL/1.0.1e-fips PHP/5.5.17
    X-Powered-By: PHP/5.5.17
    Content-Length: 68
    Content-Type: application/json

    {"error":"url not found"}

Example where the request method is not supported:

    $ curl -i -X DELETE http://localhost/php-lib-rest/examples/rest.php/hello/world
    HTTP/1.1 405 Method Not Allowed
    Date: Fri, 10 Oct 2014 14:44:25 GMT
    Server: Apache/2.4.10 (Fedora) OpenSSL/1.0.1e-fips PHP/5.5.17
    X-Powered-By: PHP/5.5.17
    Allow: GET,POST
    Content-Length: 85
    Content-Type: application/json

    {"error":"unsupported method"}

Example where a certain request is not acceptable

    $ curl -i -X POST http://localhost/php-lib-rest/examples/rest.php/hello/foo
    HTTP/1.1 400 Bad Request
    Date: Fri, 10 Oct 2014 14:44:49 GMT
    Server: Apache/2.4.10 (Fedora) OpenSSL/1.0.1e-fips PHP/5.5.17
    X-Powered-By: PHP/5.5.17
    Content-Length: 80
    Connection: close
    Content-Type: application/json

    {"error":"you cannot say \"foo!\""}
