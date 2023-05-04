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

## Server-Side Request Forgery (SSRF)

### When?

Issue can be encountered when doing an outbound HTTP request.

### What is this?

See [OWASP Server-Side Request Forgery document](https://owasp.org/www-community/attacks/Server_Side_Request_Forgery).

### Mitigations

Follow the instructions given on the [Making HTTP requests](./back-end/making-http-requests.md) page.
Internal requests needs to be distinguished from requests made for users.
