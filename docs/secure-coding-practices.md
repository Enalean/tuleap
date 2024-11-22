# Secure coding practices

This document covers description and expected resolution of security vulnerability that can be encountered when writing
code in the Tuleap codebase. The goal is to help developers identify security vulnerabilities early in the  development
process.

Contributions adding a new class of vulnerability or modifying the proposed mitigations are expected to be approved by a
member of the Tuleap Security team.

## Cross Site Scripting (XSS)

### When?

Short answer: anywhere where user provided data is displayed (i.e. anywhere).

### What is this?

See [OWASP Cross Site Scripting document](https://owasp.org/www-community/attacks/xss/).

### Mitigations

For code generating the content on the backend side, use [Mustache](./front-end/mustache.md).

For code generating the content on the frontend side, you should also use a template engine. Depending on the situation
it can be [Vue](./front-end/vue.md), [lit](https://lit.dev/docs/libraries/standalone-templates/), [hybrids](https://hybrids.js.org/)
or possibly [mustache.js](https://github.com/janl/mustache.js/).

For situations where user provided HTML code needs to be displayed it needs to be sanitized first:
* when building the content on the backend side, use [Codendi_HTMLPurifier](../src/common/include/Codendi_HTMLPurifier.class.php)
* when building the content on the frontend side, use [DOMPurify](https://github.com/cure53/DOMPurify). If you are
  writing Vue code, use [vue-dompurify-html](https://github.com/LeSuisse/vue-dompurify-html) which provides a directive
  to replace `v-html`.

## SQL Injection

### When?

Issue can be encountered when writing SQL queries.

### What is this?

See [OWAP SQL Injection document](https://owasp.org/www-community/attacks/SQL_Injection).

### Mitigations

Follow the instructions given on [backend database](./back-end/database.md) page.

## Cross Site Request Forgery (CSRF)

### When?

Issue can be encountered when processing a request modifying a state on the server.

### What is this?

See [OWASP Cross Site Request Forgery document](https://owasp.org/www-community/attacks/csrf).

### Mitigations

Make sure that requests that are expected to change a server state can only be done with the appropriate HTTP verb
(`POST`, `PUT`, `PATCH` or `DELETE`) and not with an HTTP verb that is expected to be used only to query data
(`GET`, `OPTIONS`, `HEAD`, `CONNECT` or `TRACE`).

When writing an HTML form, a CSRF synchronizer token is expected to be set and verified, See how to achieve that in [the
Mustache templating guide](./front-end/mustache.md).

## Command Injection

### When?

Issue can be encountered when creating and executing OS command containing data provided by a user.

### What is this?

See [OWASP Command Injection document](https://owasp.org/www-community/attacks/Command_Injection).

### Mitigations

Overall, passing user-supplied data to OS commands should preferably be avoided. If it is not possible due to
performance reasons or lack of alternatives:
* escape arguments: relying on the [Symfony Process component](https://symfony.com/doc/current/components/process.html)
is the preferred way to achieve that, [`escapeshellarg()`](https://www.php.net/manual/en/function.escapeshellarg) can be
used if necessary
* validate arguments against an allow list
* use `--` when possible to separate options from arguments

## Server-Side Request Forgery (SSRF)

### When?

Issue can be encountered when doing an outbound HTTP request.

### What is this?

See [OWASP Server-Side Request Forgery document](https://owasp.org/www-community/attacks/Server_Side_Request_Forgery).

### Mitigations

Follow the instructions given on the [Making HTTP requests](./back-end/making-http-requests.md) page.
Internal requests needs to be distinguished from requests made for users.

## Permissions

### When?

Everytime a new feature or endpoint is implemented.

### What is this?

See [OWASP document about access-control](https://owasp.org/www-community/Access_Control) and the [Tuleap Permissions
model](https://docs.tuleap.org/administration-guide/users-management/security/site-access.html).

### Mitigations

It must be ensured that the Tuleap Permissions model is respected and, if not possible, amended to include to cover the
change.

The general guidelines about the [expected code](./expected-code.md) must be followed (especially regarding tests) and
reviewers must have a special point of vigilance.

## Handling secrets

### When?

Special care must be taken when manipulating secrets/credentials to limit the possibility of leaking them.

### What is this?

Are considered secrets/sensitive information:
* login information such as a username/password
* Access keys and tokens (personal access keys, OAuth2 tokens…)
* Anything that can be used to authenticate or authorize accesses

### Protection

#### At rest

Secrets stored in the database are expected to be:
* hashed in an appropriate manner when the need is only to compare it against another value
  * passwords and user provided secrets must be hashed using a key derivation function designed for this use case, use
    [`StandardPasswordHandler::computeHashPassword()`](../src/common/User/Password/StandardPasswordHandler.php)
  * for randomly generated keys like personal access keys use the ["split token" pattern](../src/common/Authentication/SplitToken/)
* encrypted using [`SymmetricCrypto::encrypt()`](../src/common/Cryptography/Symmetric/SymmetricCrypto.php) if accessing
  the plaintext value is required

#### While processing a request

* never log a secret
* use [`ConcealedString`](../src/common/Cryptography/ConcealedString.php) when manipulating a sensitive string to avoid
  leaking inadvertently in a stack trace and preventing it to be serialized
* call [`sodium_memzero()`](https://www.php.net/manual/en/function.sodium-memzero.php) once you have wrapped your
  sensitive string in a `ConcealedString` in order to try to limit  its exposure as much as possible

#### In transit

* use an encrypted channel like TLS (e.g. HTTPS) when transmitting or receiving credentials
* secrets should preferably be sent in the request body instead of the URL parameters as it is less likely to be logged

## Cryptographic failures

### When?

Issue can be encountered when doing cryptographic operations such as encrypting, signing or hashing data.

### What is this?

See [OWASP Top 10 2021 - Cryptographic failures](https://owasp.org/Top10/A02_2021-Cryptographic_Failures/).

### Mitigations

* For encrypting data see the previous section "Handling secrets"
* The [`Tuleap\Cryptography`](../src/common/Cryptography/) namespace offers hard to misuse interfaces for some symmetric
 and asymmetric cryptography operations
* If your need is not covered by the existing solutions please reach out to a team member experienced in applied
  cryptography. The following APIs can be used to design a solution solving your problem:
  - [`hash()`](https://www.php.net/manual/en/function.hash.php) with SHA-256, SHA-384, SHA-512, SHA-512/256
  - [`hash_hmac()`](https://www.php.net/manual/en/function.hash-hmac.php) with SHA-256, SHA-384, SHA-512, SHA-512/256
  - [`sodium_*`](https://www.php.net/manual/en/book.sodium.php) functions