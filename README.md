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

The following functions are based on, and largely consistent with, their `pecl_http` extension (v1) counterparts:
 
 * __`http_build_url()`__
 * __`http_date()`__
 * __`http_redirect()`__
 * __`http_send_status()`__
 * __`http_send_content_type()`__
 * __`http_send_content_disposition()`__
 * __`http_send_file()`__
 * __`http_get_request_body()`__
 * __`http_get_request_body_stream()`__
 * __`http_get_request_headers()`__
 * __`http_match_request_header()`__
 * __`http_negotiate_content_type()`__
 * __`http_negotiate_language()`__

The following functions are also available:

 * __`http_get_request_header()`__
 * __`http_negotiate_request_header()`__
 * __`http_in_request_header()`__
 * __`http_build_cache_headers()`__
 * __`http_response_code()`__
 * __`http_response_code_desc()`__
 * __`http_env()`__
 * __`mimetype()`__
 * __`mime2filetype()`__

