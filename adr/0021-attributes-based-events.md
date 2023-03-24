# Usage of PHP attributes to declare listened hooks in plugins

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON, Manuel VACELET
* Date: 2023-03-03

Technical Story: [request #31113 Simplify hook listening for plugins](https://tuleap.net/plugins/tracker/?aid=31113)

## Context and Problem Statement

For plugins, listening to hooks is tedious, there is a lot of copy/paste and things are scattered at different places:

* Usage of hooks must be declared in getHooksAndCallbacks
* It's not easy to find the hook that correspond to a callback
* There is a lot of repetition of the event: at hook declaration (`getHooksAndCallbacks`), at the name of the callback, at the first parameter name.

Modern PHP has attributes, they can be used to move the hook listening declaration to the callback.

## Decision Outcome

Hooks must now be listening to with `ListeningToEvent` whenever possible for new code. Existing code
doesn't have to be converted.

### Positive Consequences

* It reduces the boilerplate to listen to hooks for plugins
* It reduces the cognitive overhead for developers who look for the hooks that corresponds to a given callback
* It allows to have better naming for callback as they are no longer tied to hook name
* Events no longer have to declare the magic `NAME` constant as class name can be used for hook declaration
* Events without NAME are hence compatible with [PSR-14](https://www.php-fig.org/psr/psr-14/)
* For conditional events (like statistics) using object events + attributes removes the need of a if statement in `getHooksAndCallbacks`.

### Negative Consequences

* It adds one more way to declare hooks:
  1. Pure string
  2. String wrapped in a constant in Event class
  3. Class based with NAME constant
  4. Class based without NAME constant
