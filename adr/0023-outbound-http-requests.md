# Management of outbound HTTP requests (SSRF protection)

* Status: Accepted
* Deciders: Thomas GERBET
* Date: 2023-04-03

Technical Story: [request #31580: Filter outbound HTTP requests][9]

## Context and Problem Statement

Tuleap sends HTTP requests for multiple reasons.
Some of those requests are considered internal because they are entirely under the control of the application
(e.g. sending messages to the realtime server). Some others are however under the control of the users and can try to
access any servers (e.g. webhooks, calls to the GitLab or JIRA APIs...).

The requests made on user-defined URLs are problematic because they can try to access private services or cloud server
meta-data. This class of attack is called Server-Side Request Forgery and is [one of the items of the OWASP Top 10 2021][0].

Tuleap needs a way to protect those HTTP requests from malicious users. The need for such protection is growing because
deployments in cloud environments are becoming common and more and more features rely on reaching a third party service
over HTTPS.

## Considered Options

* Implement the protection in PHP
* Use a dedicated request proxy ([Stripe Smokescreen][5])

## Decision Outcome

Chosen option: Use a dedicated request proxy ([Stripe Smokescreen][5]) as it is the most versatile option and it is also
the one that is the less likely to break.

## Pros and Cons of the Options

### Implement the protection in PHP

Some PHP implementations exist to implement the different layers of protection preventing SSRF require. The
[SafeURL library][1] and its ancestor [SafeCurl][2] are examples of that.

* Good, because it does not require an additional service
* Bad, because due to the primitives available in PHP it is hard to implement correctly
  ([GreHack CTF Challenge][3], [challenge write-up][4]) without limitations
* Bad, because we cannot use it for elements that are not written in PHP (e.g. `git`)

### Use a dedicated request proxy ([Stripe Smokescreen][5])

* Good, because it can be used for our PHP code and any other services supporting an `HTTP CONNECT` proxy
* Good, because it is a battle tested solution
* Bad, because it requires to run an additional service

## Implementation details

Tuleap already has a `sys_proxy` setting. The SSRF protection must be disabled when this setting is used since it
becomes not possible for Tuleap to know the transformation that might be applied by this proxy.

The following IP ranges should be blocked by default:
* [RFC 1918][6] (`10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`)
* [RFC 4193][7] (`fc00::/7`) / or more globally anything that is outside the global unicast range `2000::/3`
* [RFC 3927][8] (`169.254.0.0/16`)
* Localhost addresses (`127.0.0.1/8`, `::1`)


A new setting should be added to allow system administrators to redefine this blocklist to fit their needs.

## Links

* [OWASP Top 10 2021: Server-Side Request Forgery][0]
* [PortSwigger - Server-side request forgery](https://portswigger.net/web-security/ssrf)
* [CWE-918](https://cwe.mitre.org/data/definitions/918.html)
* [Blogpost from IncludeSecurity: Mitigating SSRF in 2023](https://blog.includesecurity.com/2023/03/mitigating-ssrf-in-2023/)

[0]: https://owasp.org/Top10/en/A10_2021-Server-Side_Request_Forgery_(SSRF)/
[1]: https://github.com/includesecurity/safeurl-php
[2]: https://github.com/wkcaj/safecurl
[3]: https://github.com/GreHack/CTF-challs/tree/743c4bdc9519cccf7124c8be9838270238729e21/2018/Web/350%20-%20Microservices
[4]: https://github.com/LeSuisse/slides/blob/0618361969d1b0c3f928aed0f94cd1b1d54ecfd8/Grenoble_Cybersecurity_Meetup/GreHack18_CTF/grehack18-ctf.md#microservices---350-web
[5]: https://github.com/stripe/smokescreen
[6]: https://rfc-editor.org/rfc/rfc1918.html
[7]: https://rfc-editor.org/rfc/rfc4193.html
[8]: https://rfc-editor.org/rfc/rfc3927.html
[9]: https://tuleap.net/plugins/tracker/?aid=31580
