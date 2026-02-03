---
status: accepted
date: 2026-01-30
decision-makers: Joris MASSON
consulted: Clarck ROBINSON, Kevin TRAINI, Nicolas TERRAY
informed: Clarisse DESCHAMPS, Lucas MUSY, Manuel VACELET, Marie-Ange GARNIER, Martin GOYOT, Thomas GERBET, Thomas GORKA
---

# Use named Query parameters instead of generic JSON query

## Context and Problem Statement

[request #46747][0]: ADR Use named Query parameters instead of generic JSON query

We sometimes need to refine the result of a REST API `GET` call, either by filtering out some items, or by including some more items, or by changing the shape of each item (for example: "full" representation). How should we represent this in the request to the REST API ?

## Considered Options

* Use a generic [URI Query part][1] called "query" with a JSON value
* Use a named [URI Query part][1] for each parameter

## Decision Outcome

Chosen option: "Use a named URI Query part for each parameter", because it comes out best in the comparison (see below). "generic query" parameters should be avoided.

### Consequences

* Neutral, because we will need to keep maintaining existing "generic query" parameters in order to avoid API breaking changes.
* Neutral, because "named" parameters already co-existed with "generic query" parameters, so overall API consistency is neither improved nor reduced. "generic query" parameters are not very widespread with 16 occurrences at the time of writing.

### Confirmation

* All newly-introduced REST API parameters are "named URI Query parts" (see below).

## Pros and Cons of the Options

### Use a generic [URI Query part][1] called "query" with a JSON value

Example:

```
/api/projects/${project_id}/milestones?query=%7B%22status%22%3A%22open%22%2C%22representation%22%3A%22full%22%7D
// query is JSON-encoded and then URI-escaped
```

```json5
// query value looks like this once un-escaped:
{
    "status":  "open",
    "representation": "full"
}
```

* Good, because it's easy to add support for new keys or values.
* Neutral, because it does not protect us from breaking changes. If we ever remove the "status" key or the "open" value, it will still be an API breaking change.
* Neutral, because it allows for "secret" keys or values. We could add un-documented keys or values that we "could" argue would escape breaking changes. If we are being strict about it, un-documented keys and values are still part of the API and would still cause issues if removed. The bad side of this is that we must also document manually (via a doc-block comment) all supported keys and values.
* Bad, because the URI is harder to read (for example in logs), you need to first un-escape and JSON decode the query.
* Bad, because [Restler][3] cannot validate such parameters. We need to either roll our own validation, or (better) use [Valinor][2] to parse and validate it.
* Bad, because it forces a dependency on JSON for clients. Most clients use JSON format for our REST API, but Restler also supports XML. In this case, when making the request, you would still be forced to use JSON "just" for the query parameter.

### Use a named [URI Query part][1] for each parameter

Example:

```
/api/projects/${project_id}/milestones?status=open&representation=full
```

The parameters are "plain-old" keys and values. They can be string type, or number type just like we're used to with "limit" and "offset" for pagination.

* Good, because it's easy to add support for new keys or values.
* Good, because Restler will document these parameters as usual.
* Good, because the URI is easy to read.
* Good, because [Restler][3] can [validate it][4] somewhat with `@choice` for Enums, `@min` `@max` for numbers, etc.
* Good, because it only needs URI escaping, it does not depend on JSON.
* Neutral, because it does not protect us from breaking changes. If we ever remove the "status" key or the "open" value, it will still be an API breaking change.
* Neutral, because it might be impractical to configure "secret" un-documented keys or values. At the time of writing, it's unknown whether it's possible.

## More Information

* [RFC 3986 Uniform Resource Identifier (URI): Generic Syntax][1]
* [Restler 3 supported param annotations][4]

[0]: https://tuleap.net/plugins/tracker/?aid=46747
[1]: https://www.rfc-editor.org/rfc/rfc3986#section-3.4
[2]: https://valinor.cuyz.io/2.3/
[3]: https://github.com/Enalean/Restler
[4]: https://restler3.luracast.com/param.html
