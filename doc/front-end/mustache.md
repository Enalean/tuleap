# Mustache templating

All new code must output content based on
[Mustache](https://mustache.github.io/) templates. The code is typically
organized in 3 files:

-   The template
-   The presenter
-   The calling code (in a Controller for instance)

Example of template:

``` html
<h1>Hello</h1>

<p>Welcome to {{ my_title }}</p>
<!-- For readability, please note :                  -->
<!--   * the spaces between {{, variable name and }} -->
<!--   * the use of snake_case for variables         -->
```

Example of Presenter

``` php
declare(strict_types=1);

class Presenter
{
    public string $my_title;

    public function __construct()
    {
        $this->my_title = "My title";
    }
}
```

Example of calling code:

``` php
$renderer = TemplateRendererFactory::build()->getRenderer('/path/to/template/directory');

// Output content directly (to the browser for instance)
$renderer->renderToPage('template_name', new Presenter());

// Return the content for futur reuse
$string = $renderer->renderToString('template_name', new Presenter());
```

For existing code, it's acceptable to output content with "echo" to
keep consistency.

## Escaping

You should rely on Mustache `{{ }}` notation to benefit from automatic
escaping.

If you need to put light formatting in you localised string, then you
should escape beforehand and use `{{{ }}}` notation. As it produces a
code that is less auditable (reviewer has to manually check if
injections are not possible), the convention is to prefix the variable
with `purified_` and manually purify the variable in the presenter.

``` php
declare(strict_types=1);

class Presenter
{
    public string $purified_description;

    public function __construct()
    {
        $this->purified_description = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('key1', 'key2', 'https://example.com'),
            CODENDI_PURIFIER_LIGHT
        );
    }
}

// .tab file:
// key1    key2    This is the <b>description</b> you can put <a href="$1">light formatting</a>

// .mustache file:
// <p>{{{ purified_description }}}</p>
```

## Secure forms against CSRF

All state-changing actions MUST be protected against CSRF
vulnerabilities. In order to do that, a specific token must be added to
your forms and verified before the execution of the action.

Example:

Controller.php:

``` php
declare(strict_types=1);

namespace Tuleap/CsrfExample;

use CSRFSynchronizerToken;
use TemplateRendererFactory;

class Controller
{
    public function display() : string
    {
        $csrf_token = CSRFSynchronizerToken(CSRF_EXAMPLE_BASE_URL . '/do_things');
        $presenter  = new Presenter($csrf_token);
        $renderer   = TemplateRendererFactory::build()->getRenderer(CSRF_EXAMPLE_TEMPLATE_DIR);

        $renderer->renderToPage('csrf-example', $presenter);
    }

    public function process() : void
    {
        $csrf_token = CSRFSynchronizerToken(CSRF_EXAMPLE_BASE_URL . '/do_things');
        $csrf_token->check();

        do_things();
    }
}
```

Presenter.php:

``` php
declare(strict_types=1);

namespace Tuleap/CsrfExample;

use CSRFSynchronizerToken;

class Presenter
{
     public CSRFSynchronizerToken $csrf_token;

    public function __construct(CSRFSynchronizerToken $csrf_token)
    {
        $this->csrf_token = $csrf_token;
    }
}
```

csrf-example.mustache:

``` html
<form method="post">
    {{# csrf_token }}
        {{> csrf_token_input }}
    {{/ csrf_token }}
    <input type="submit">
</form>
```

For existing code rendering HTML without using templates, it can be
acceptable to use the fetchHTMLInput method of the CSRFSynchronizerToken
class.

## Internationalization

See [Internationalization](../internationalization.md) for
details.
