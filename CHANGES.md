# Release History

## 0.3.0
* **BREAKS API**, move `Service` to `fkooman\Rest` namespace
* Introduce plugins for `Service` class to execute code before
  the REST matcher starts, or for every match
* Add `BasicAuthentication` plugin

## 0.2.0
* **BREAKS API** for `Request` class
* Introduce `Service` class to make it much easier to write a REST router, 
  removed the `matchRest()` method from `Request` class, see `examples/` 
  directory for new example using `Service` class
* Introduce `JsonResponse` class so you do not need to do any JSON encoding/
  decoding and set the `Content-Type` header to `application/json`
* Ability to generate API docs, see README.md

## 0.1.0 
* Initial release based on Http classes of php-rest-service 0.9.3, **breaks
  API** from php-rest-service 0.9.3 due to namespace changes
