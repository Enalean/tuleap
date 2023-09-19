# Tuleap Project Sidebar

An independent custom element that can be used to get the same project sidebar than the one used in Tuleap projects.

## Installation

```
npm install @tuleap/project-sidebar
```

## Usage

In your HTML content, add the element:

```html
<tuleap-project-sidebar config="..."></tuleap-project-sidebar>
```

### Config

The `config` property is mandatory. It is a JSON encoded string that you can retrieve from the endpoint you use to
communicate with Tuleap. For test purposes you can find the description of expected schema in
[configuration.ts](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=refs%2Fheads%2Fmaster&f=src%2Fscripts%2Flib%2Fproject-sidebar-internal%2Fsrc%2Fconfiguration.ts)
and a complete example in
[project-sidebar-example-config.ts](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=refs%2Fheads%2Fmaster&f=src%2Fscripts%2Flib%2Fproject-sidebar-internal%2Fsrc%2Fproject-sidebar-example-config.ts).
For production, the configuration can be retrieved from the REST endpoint `GET /api/projects/:id/3rd_party_integration_data`.
As it is likely to be too costful to retrieve the information each time you display the sidebar we suggest you retrieve
it once and then cache it for some time. The cache needs to be done per user and per project.

### Collapse of the sidebar

The sidebar also accepts an attribute `collapsed` to collapse it. You can watch this attribute with a
[MutationObserver](https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver) to detect when the sidebar is
collapsed or opened.

The collapse behavior can be removed by adding an attribute `no-collapse-button` to the element, for example:
```html
<tuleap-project-sidebar config="..." no-collapse-button></tuleap-project-sidebar>
```

Collapse of the sidebar can be disabled via `is_collapsible` property in `config` (see above). If this property is provided
and is `false`, then sidebar cannot be collapsed (regardless of the `collapsed` and `no-collapse-button` attributes).

**Note:** ⚠️ collapse of the sidebar is deprecated. `is_collapsible`, `collapsed`, and `no-collapse-button` will be
removed in a future version of this component.

### Events

The custom element throws a [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent)
`show-project-announcement` when the user want to see the project announcement.

## Load

You will need to load the code defining the custom element (see below) and to load a stylesheet with the [CSS
variables](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties) used by Tuleap. Access to the
[appropriate flavor of the stylesheet](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=refs%2Fheads%2Fmaster&f=src%2Fthemes%2Ftlp%2Fsrc%2Fscss%2Fcomponents%2F_css-var-root.scss)
will be provided through the communication channel you have with Tuleap.

### Loading the custom element when using a bundler (Webpack, Rollup, Vite…)

Import the package in one of the script you load

```js
import "@tuleap/project-sidebar";
```

and import the CSS in one of your stylesheet
```css
import "@tuleap/project-sidebar";
```

### Loading the script without using a bundler

Insert the script `dist/project-sidebar.umd.cjs` and the stylesheet `dist/style.css` into the page.
