# Tuleap Project Sidebar for internal usage

## Installation

```
pnpm install @tuleap/project-sidebar-internal
```

## Usage

In your HTML content, add the element:

```html
<tuleap-project-sidebar config="..."></tuleap-project-sidebar>
```

Install the element from your JS/TS code:

```ts
import { installProjectSidebarElement } from "@tuleap/project-sidebar-internal";

installProjectSidebarElement(window, (): void => {
    // Do something during the installation
});
```

In your SCSS code load the stylesheet to set the icon font family

```scss
import "@tuleap/project-sidebar-internal";
```

To see how to interact with the sidebar element, please consult [@tuleap/project-sidebar README](../project-sidebar/README.md).

## Development

You can start a dev server with `pnpm run dev`.
