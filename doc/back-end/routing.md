# Routing

For years Tuleap routing has been a mess but it's not a reason to add
more. All new routes must be handled by FrontRouter.

FrontRouter leverages Nikita Popov FastRoute package.

## Core

Adding a new route to core should be done in `RouteCollector` class (in
`src/common/request` if you don't use an IDE). The route definition
documentation can be found in
[FastRoute](https://github.com/nikic/FastRoute) package. Here is a
sample for the front homepage:

``` php
$r->get('/', [__CLASS__, 'getSlash']);
```

This means that instantiation of controller associated to `/` route will
be done in `getSlash` method of `RouteCollector` class.

The handler associated to a route must implement a specific,
`DispatchableWithRequest`, interface. See next for details

## Plugins

Plugins should not provide php front script either. If you need to
register a route for a plugin there is an event to listen to:
`CollectRoutesEvent`.

See bellow an example for 'stuff' plugin that want to expose the
following routes:

-   `GET /plugins/stuff`
-   `GET /plugins/stuff/admin`
-   `GET /plugins/stuff/admin/`
-   `GET /plugins/stuff/admin/index.php`
-   `POST /plugins/stuff/admin`

``` php
public function routeGetAdmin(): Tuleap\Stuff\Admin\IndexController
{
    return new Tuleap\Stuff\Admin\IndexController();
}

public function routePostAdmin(): Tuleap\Stuff\Admin\UpdateController
{
    return new Tuleap\Stuff\Admin\UpdateController();
}

public function routeGetSlash(): Tuleap\Stuff\StuffIndexController
{
    return new Tuleap\Stuff\StuffIndexController();
}

#[Tuleap\Plugin\ListeningToEventClass]
public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event) : void
{
    $event->getRouteCollector()->addGroup('/plugins/stuff', function (FastRoute\RouteCollector $r) {
        $r->get('/admin[/[index.php]]', $this->getRouteHandler('routeGetAdmin'));
        $r->post('/admin', $this->getRouteHandler('routePostAdmin'));
        $r->get('/', $this->getRouteHandler('routeGetSlash'));
    });
}
```

## Controller

Rule of thumb: you should have one controller per route. It's an
anti-pattern to have a route that collect several verbs and return one
controller for both. If you have common code between 2 controllers it
should be extracted in a 3rd class and injected in constructor (no
inheritance!)

Controllers must implements a `DispatchableWithRequest` route. As of
today there are 2 options:

-   `Tuleap\Request\DispatchableWithRequest`
-   `Tuleap\Request\DispatchableWithRequestNoAuthz`

`Tuleap\Request\DispatchableWithRequest` is the preferred form and will
handle for you a bunch of built-in protections (private / public
projects, restricted users, platform access controls, etc). It's the
preferred form to use.

`Tuleap\Request\DispatchableWithRequestNoAuthz` is the risky option
where all Tuleap protections are off. **You must re-implement** all the
access control logic (even authentication) when you implement this
interface. It's a dangerous way to go but it's your only option when
you need full control with HTTP handling, doing basic auth, etc.

### Exceptions

In your controllers you can throw 2 exceptions that will be
automatically converted in error messages:

-   `Tuleap\Request\ForbiddenException` when you refuse access to some
    one (leading to 403 error)
-   `Tuleap\Request\NotFoundException` when resource doesn't exist
    (leading to 404 error)

All other exceptions are caught and converted to 500 error.
