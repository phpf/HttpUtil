HttpUtil
========

Utilities for working with HTTP requests and responses.

 * PHP 5.3+
 * No dependencies
 * Cross-platform

####Primary features
 * Full set of functions for retrieving request headers
 * Utilities similar to those found in the pecl_http extension (v1), such as `http_get_request_headers()` and `http_negotiate_content_type()`.
 * some general-use classes and functions for negotiating request header values based on `q`
 * a fallback for `http_response_code()` (PHP < 5.4)
 * Mimetype helpers and some other stuff

###Function list

Functions marked with _*_ are based on and largely consistent with their pecl_http v1 counterparts.

 * `http_env()`
 * `http_response_code()`
 * `http_response_code_desc()`
 * `http_build_cache_headers()`
 * `http_build_url()` _*_
 * `http_date()` _*_
 * `http_redirect()` _*_
 * `http_send_status()` _*_
 * `http_send_content_type()` _*_
 * `http_send_content_disposition()` _*_
 * `http_send_file()` _*_
 * `http_get_request_body()` _*_
 * `http_get_request_body_stream()` _*_
 * `http_get_request_headers()` _*_
 * `http_get_request_header()`
 * `http_in_request_header()`
 * `http_match_request_header()` _*_
 * `http_negotiate_content_type()` _*_
 * `http_negotiate_language()` _*_
 * `http_negotiate_request_header()`
 * `mimetype()`
 * `mime2filetype()`

