# Front End

Tuleap front end can be divided in two worlds

-   Mostly static pages that are rendered server side using mustache
    templating (with some vanilla JS for simple interactions).
-   Rich, dynamic, pages that are rendered client side using Vuejs.

Front end build system is based on [Vite](https://vitejs.dev/) with some historic parts using Webpack.

Tuleap uses its own design system, how to use it and the available components can be seen on [design-system.tuleap.net](https://design-system.tuleap.net/).

Historically you will also find:

-   AngularJS 1.X code.
-   JQuery code.
-   Prototype / Scriptaculous code.
-   Good old php3&4/mysql scripts where DB, HTML and JS are mixed.

Those are usages and technologies of the past and must be forgotten. New
contributions should not be based on them.

* [Mustache](./front-end/mustache.md)
* [CSS](./front-end/css.md)
* [Javascript](./front-end/javascript.md)
* [Vue](./front-end/vue.md)
* [Tests](./front-end/tests.md)
* [AngularJS](./front-end/angular.md)
* [Internal libs](./front-end/internal-libs.md)
* [Bump dependencies](./front-end/bump-dependencies.md)
