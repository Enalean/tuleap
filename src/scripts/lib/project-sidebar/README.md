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

The `config` property is mandatory. It is a JSON encoded string that you can retrieve from the endpoint you use to
communicate with Tuleap. For test purposes you can find the description of expected schema in
[configuration.ts](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=refs%2Fheads%2Fmaster&f=src%2Fscripts%2Flib%2Fproject-sidebar-internal%2Fsrc%2Fconfiguration.ts)
and a complete example in
[project-sidebar-example-config.ts](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=refs%2Fheads%2Fmaster&f=src%2Fscripts%2Flib%2Fproject-sidebar-internal%2Fsrc%2Fproject-sidebar-example-config.ts).

The sidebar also accepts an attribute `collapsed` to collapse it. You can watch this attribute with a
[MutationObserver](https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver) to detect when the sidebar is
collapsed or opened.

The custom element throws a [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent)
`show-project-announcement` when the user want to see the project announcement.

You will also need to load the code defining the custom element (see below) and to load a stylesheet with the [CSS
variables](https://developer.mozilla.org/en-US/docs/Web/CSS/Using_CSS_custom_properties) used by Tuleap. Access to the
[appropriate flavor of the stylesheet](https://tuleap.net/plugins/git/tuleap/tuleap/stable?a=blob&hb=refs%2Fheads%2Fmaster&f=src%2Fthemes%2Ftlp%2Fsrc%2Fscss%2Fcomponents%2F_css-var-root.scss)
will be provided through the communication channel you have with Tuleap.

### Loading the script when using a JS bundler (Webpack, Rollup, Vite…)

Import the package in one of the script you load

```js
import "@tuleap/project-sidebar";
```

### Loading the script without using a JS bundler

Insert the script `dist/project-sidebar.umd.js` into the page.
