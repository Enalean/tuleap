# Remove LDAP write

* Status: Accepted
* Deciders: Manuel Vacelet
* Date: 2023-06-14

Technical Story: [request #31528 Remove LDAP Write part of tuleap LDAP plugin][1]

## Context and Problem Statement

LDAP write is the ability for Tuleap to write & update user information inside an LDAP directory transparently.

The feature exists since 2014 in Tuleap code base and was used for two cases:
- Allow developers to have a development environment close to the actual production of customers (always connected to an LDAP)
- Solves the problem of auth on https://gerrit.tuleap.net: use tuleap.net credentials to connect.

The second point is no longer needed as we now rely on OIDC for gerrit login.

The first one remains relevant but no longer need to be part of the production code.

## Considered Options

- Keep LDAP write code inside LDAP plugin
- Move the juicy parts in a dedicated, developers only, CLI

## Decision Outcome

Chosen option: Move the juicy parts in a dedicated, developers only, CLI. Mainly to reduce code complexity in an already too complex plugin.

## Pros and Cons of the Options

### Keep LDAP write code inside LDAP plugin

That's the easiest path, there is nothing to change.
* Good, because it already works since almost 10 years
* Good, because it's transparent for developers (they create & update users in Tuleap web UI and it's automatically propagated to LDAP)
* Bad, because it embeds development only code in a very sensitive part (LDAP is for authentication).

### Move the juicy parts in a dedicated, developers only, CLI

* Good because it allows to remove code from LDAP plugin, reducing the surface of attack and cognitive overload.
* Bad because it's more complex for developers to deal with users, they have to create them manually in LDAP first.

A dedicated [set of commands](./../doc/ldap.md) are added to reduce the inconvenience for developers.

[1]: https://tuleap.net/plugins/tracker/?aid=31528
