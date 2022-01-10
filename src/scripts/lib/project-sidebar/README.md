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
[configuration.ts](./src/configuration.ts) and a complete example in
[project-sidebar-example-config.ts](./src/project-sidebar-example-config.ts).

The sidebar also accepts an attribute `collapsed` to collapse it. You can watch this attribute with a
[MutationObserver](https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver) to detect when the sidebar is
collapsed or opened.

The custom element throws a [CustomEvent](https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent)
`show-project-announcement` when the user want to see the project announcement.

### Loading the script when using a JS bundler (Webpack, Rollup, Vite…)

Import the package in one of the script you load

```js
import "@tuleap/project-sidebar";
```

### Loading the script without using a JS bundler

Insert the script `dist/project-sidebar.umd.js` into the page.
