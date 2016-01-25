# Release History

## 1.1.0 (2016-01-25)
- implement `Service::addModule` to aid in compartmentalizing services.

## 1.0.6 (2015-12-25)
- remove `ErrorException` handling for exceptions not from `fkooman/http`, 
  they are unexpected exceptions. Use e.g. something like `filp/whoops` for
  catching these kind of errors. Our implementation was bad in that it did not 
  catch all errors anyway.

## 1.0.5 (2015-10-07)
- update `fkooman/http` to set `Content-Length` on responses

## 1.0.4 (2015-09-07)
- fix unit tests running on CentOS 6

## 1.0.3 (2015-09-07)
- move HTTP classes to `fkooman/http` and depend on it

## 1.0.2 (2015-08-05)
- FIX: a small issue in regular expression matcher where it would not catch
  single length parameters

## 1.0.1 (2015-07-20)
- FIX: actually initialize plugins before using them

## 1.0.0 (2015-07-20)
- initial release
