# Release History

## 0.4.7
- support multiple request methods per match line

## 0.4.6
- implement head shortcut as well

## 0.4.5
- implement get,put,post,delete,options calls in Service class mapping
  for easier syntax

## 0.4.4
- also allow "*" as a pattern in addition to null to match all URIs

## 0.4.3
- update to new `fkooman/json` version

## 0.4.2
- update to new `fkooman/json` version

## 0.4.1
- implement support for obtaining the entire path as a callback parameter
  if `null` is used as a pattern match

## 0.4.0
- rename package to fkooman/rest
- update fkooman/json dependency

## 0.3.1
- update to php-lib-json 0.3.x

## 0.3.0
- **BREAKS API**, move `Service` to `fkooman\Rest` namespace
- Introduce plugins for `Service` class to execute code before
  the REST matcher starts, or for every match
- Add `BasicAuthentication` plugin

## 0.2.0
- **BREAKS API** for `Request` class
- Introduce `Service` class to make it much easier to write a REST router, 
  removed the `matchRest()` method from `Request` class, see `examples/` 
  directory for new example using `Service` class
- Introduce `JsonResponse` class so you do not need to do any JSON encoding/
  decoding and set the `Content-Type` header to `application/json`
- Ability to generate API docs, see README.md

## 0.1.0 
- Initial release based on Http classes of php-rest-service 0.9.3, **breaks
  API** from php-rest-service 0.9.3 due to namespace changes
