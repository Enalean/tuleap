# Making HTTP requests

This section explains how to do outbound HTTP requests in the Tuleap codebase.

## Categories of outbound HTTP requests

HTTP requests made by Tuleap can be classified in 2 categories:
* internal requests made to infrastructure components entirely under the control of Tuleap (e.g. calls made to the
Realtime server)
* requests using information provided by users in the URL or headers (e.g. webhooks, CI jobs…)

Making the distinction between the two is important in order to protect against malicious users trying to do Server-Side
Request Forgery (SSRF) attacks. You can find more information about this in [ADR-0023](../decisions/0023-outbound-http-requests.md).

## Getting an HTTP client

You can retrieve an HTTP client using the [`Tuleap\Http\HttpClientFactory` class](../../Http/HttpClientFactory.php).
It proposes methods to build the HTTP client with sane default:
* `HttpClientFactory::createClient()` gives a [PSR-18 HTTP client][0]
* `HttpClientFactory::createAsyncClient()` gives an [HTTPPlug async client][1] which can be useful when sending multiple
 requests at the same time
* `HttpClientFactory::createClientForInternalTuleapUse()` gives [PSR-18 HTTP client][0] to be used for internal requests

## Note on the filtering of outbound HTTP requests in the development environment

In the development environment outbound HTTP requests are allowed by default to IP addresses in the RFC 1918 ranges
(`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`). This is done this way to not cause additional troubles to reach
containers started by our Docker Compose stack. It should be noted **this is not a sane default in production environment**.


[0]: https://www.php-fig.org/psr/psr-18/
[1]: https://docs.php-http.org/en/latest/httplug/tutorial.html#using-an-asynchronous-client
