# Release History

## 0.8.9
- add new `Url` class, deprecates `Uri` in the near future

## 0.8.8
- more pathinfo fixes

## 0.8.7
- make it possible to disable redirects when no pathinfo is provided,
  i.e. if you run an app that just uses the script name but does not 
  do any REST stuff

## 0.8.6
- redo default route handling

## 0.8.5
- also log the URL in case it was missing
- also log the request method that was not supported

## 0.8.4
- a HEAD request will return the same as a GET response except the 
  body (issue #9)

## 0.8.3
- allow per route disabling of referrer check, for REST calls

## 0.8.2
- implement `setReferrerCheck()` method to enable HTTP Referrer check to 
  avoid CSRF protection for POST/PUT/DELETE request (disabled by default 
  for now)

## 0.8.1
- write all exceptions to `error_log`
- make it possible to disable plugins by default and enable them on a per 
  route basis (using `enablePlugins` route config)

## 0.8.0
- make it possible to provide route config to plugins to support e.g. optional 
  authentication (issue #8)
- support using default values for callback parameters if plugin does not 
  provide the required object (issue #7)

## 0.7.5
- fix defaultRoute when using non-beautified URLs (issue #3)
- deal with edge case when no REST rules are defined and 
  `MethodNotAllowedException` (issue #2)

## 0.7.4
- modernize `fkooman\Http\Uri` and add some URL normalizations
- introduce `fkooman\Http\Uri::isValid`

## 0.7.3
- only initialize a http session when it is actually used
- make it possible to better configure the session
- add session example
 
## 0.7.2
- also support FormResponse for exceptions
- cleanup exception handling and extending

## 0.7.1
- include scriptname in `getRoot()` and `getAbsRoot()` if no URL rewriting is
  used

## 0.7.0
- **BREAKING**: introduce more generic `matchOptions` parameter with a 
  `skipPlugins` key to list plugins to skip for that particular match
- bring back `FormResponse`
- **BREAKING**: `Request::getAbsRoot()` returns the absolute path to the app, 
  no longer the base directory. Use `getBaseDir()` for the absolute base
  dir
- **BREAKING**: remove all internal exceptions and use SPL exceptions, 
  except for the HTTP exceptions that correspond to HTTP error codes
- **BREAKING**: write a better error handler that 
- return the correct content type in error cases depending on `Allow` header 
  sent by client
- **BREAKING**: remove `registerBeforeMatchingPlugin` method in `Service`
- **BREAKING**: rename `registerBeforeEachMatchPlugin` to 
  `registerOnMatchPlugin`
- make `getAppRoot()` also work when no URL rewriting takes place, i.e.: if
  the script is still part of the REQUEST_URI the script name will also be
  included in the `getAppRoot()` response

## 0.6.8
- implement `getAppRoot()` on `Request` object to get the URL path directory
  the script is located in, useful for e.g. rewriting URLs

## 0.6.7
- add generic `UserInfo` class for the authentication plugins to use to 
  determin the user ID of the authenticated user

## 0.6.6
- set the default route to `/` instead of failing, useful for using the 
  `Service` class when not using the routing capabilities using `PATH_INFO`

## 0.6.5
- avoid getting double '//' when using `setDefaultRoute()` together
  with Apache rewrites

## 0.6.4
- use `htmlspecialchars()` on message when showing HTML error pages
- add `FormResponse` class
- fix `Response` class to not call `setContent(null)` and
  `setContentFile(null)` on object creation

## 0.6.3
- implement support for a query parameter (`_index`) instead of specifying
  it directly in the path info which will give some issues with Apache/PHP 
  already urldecoding it before passing it to the Request object resulting 
  in a broken path info

## 0.6.2
- make matchAll parameter always available to callbacks

## 0.6.1
- update version of `fkooman/json`
- make the `Request` parameter to the `run()` method optional, the `Service` 
  class will create the request itself from `IncomingRequest` instead

## 0.6.0 
**Breaking API changes**:
- modify the exceptions to have a description field after the message field
- remove all the rest plugins and move them to separate projects

Other changes:
- fix Service class when no default route is set but there is no path 
  specified in PATH_INFO
- redirect is now 302 and not 301 in case of default route

## 0.5.3
- fix `MellonAuthentication` plugin

## 0.5.2
- add `MellonAuthentication` plugin for SAML support to authenticate users
- `BasicAuthentication` plugin now returns a UserInfo object so it becomes
  available to the callback function by 'catching' the UserInfo type
- fix a small bug with `getPostParameter()` where the requested key points to
  an array
- add method override support in form post for DELETE and PUT by setting the
  hidden form field "_METHOD" to either "PUT" or "DELETE"
- implement default route support using `Service::setDefaultRoute`
- add `RedirectResponse` class

## 0.5.1
- fix bug with registerBeforeEachMatchPlugin where skipping a plugin didn't 
  work if the first match was not the correct match
- refactored more matching code to make it more robust, remove some FIXMEs

## 0.5.0
**Breaking API changes**:
- Removed `NotModifiedException` as this is expected behavior, not an error
- Make `getResponse()` of Exceptions `protected`, you will have to call
  `getHtmlResponse()` or `getJsonResponse()` now from your application
- Update the BasicAuthentication plugin to require the use of secure 
  password verification created using password_hash() instead of simple 
  (unhashed) string compare
- Modify the signature of `UnauthorizedException`, allow to specify 
  authparams as 3rd parameter for realm and error (in case of Bearer)
- The `Service` class now takes the `Request` object parameter in 
  the `run()` method and not in the constructor
- Move the exceptions `UriException`, `RequestException`, `ResponseException`
  and `IncomingRequestException` in the `fkooman\Http\Exception` namespace
- The variables in your callback need to have the same name as the matcher, so
  if your match is `/:foo/:bar/` your capture parameters need to be called 
  `$foo` and `$bar`. For the `*` match case the name `$matchAll` needs to be 
  used

Other changes:
- Introduce the option to get the Request object and objects from plugins by 
  specifying their prototype as a parameter of your callback function, you no 
  longer need `use ($request)` in your closure definition.
- Plugins can now return objects that can be captured in the callback function,
  all objects are passed to the callback if a type hint for that specific 
  returned object is available in the callback. All objects, except Response
  objects are returned to the callback. Response objects are sent back to the 
  client immediately
   
## 0.4.11
- make better use of included HttpExceptions
- implement `getResponse()` method for (derived) HttpExceptions, both JSON and
  HTML response supported by parameter. `true` is JSON, `false` is HTML
- cleanup example
- add MethodNotAllowedException
- revert Response to not expose statusCodes anymore

## 0.4.10
- add some Http response exceptions for use by applications

## 0.4.9
- initial version of very simple Session handler

## 0.4.8
- add `isHttps()` method to `Request` object to determine if the 
  connection was made over HTTPS
- add `getBaseUri()` method to `Uri` object
- normalize Uri by stripping ports if they are the default ports
  for HTTP (80) and HTTPS (443)

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
