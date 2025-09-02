/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Meta, StoryObj } from "@storybook/web-components-vite";
import { html, type TemplateResult } from "lit";
import "./DropdownWrapper";
import "./dropdown.scss";

type DropdownProps = {
    story:
        | "base"
        | "with submenu"
        | "with tabs"
        | "with filter"
        | "split button"
        | "large split button"
        | "unique icon trigger";
    top_align: boolean;
    disabled_options: boolean;
    trigger: "click" | "hover-and-click";
    keyboard: boolean;
    hypertext: boolean;
};

function getMenuClasses(args: DropdownProps): string {
    const classes = ["tlp-dropdown-menu"];
    if (args.top_align) {
        classes.push("tlp-dropdown-menu-top");
    }
    return classes.join(" ");
}

function getItemClasses(args: DropdownProps): string {
    const classes = ["tlp-dropdown-menu-item"];
    if (args.disabled_options && args.hypertext) {
        classes.push("tlp-dropdown-menu-item-disabled");
    }
    return classes.join(" ");
}

function getDropdownItemsAsLinks(args: DropdownProps): TemplateResult {
    return html`<span class="tlp-dropdown-menu-title" role="menuitem">Favorites</span>
        <a href="https://example.com" class=${getItemClasses(args)} role="menuitem">User story</a>
        <a href="https://example.com" class=${getItemClasses(args)} role="menuitem">Task</a>
        <span class="tlp-dropdown-menu-title" role="menuitem">Others</span>
        <a href="https://example.com" class=${getItemClasses(args)} role="menuitem">Epic</a>
        <a href="https://example.com" class=${getItemClasses(args)} role="menuitem">Test case</a>
        <span class="tlp-dropdown-menu-separator" role="separator"></span>
        <a
            href="https://example.com"
            class="${getItemClasses(args)} tlp-dropdown-menu-item-danger"
            role="menuitem"
            >External request</a
        >`;
}

function getDropdownItemsAsButtons(args: DropdownProps): TemplateResult {
    return html`<span class="tlp-dropdown-menu-title" role="menuitem">Favorites</span>
        <button
            type="button"
            class=${getItemClasses(args)}
            ?disabled=${args.disabled_options}
            role="menuitem"
        >
            User story
        </button>
        <button
            type="button"
            class=${getItemClasses(args)}
            ?disabled=${args.disabled_options}
            role="menuitem"
        >
            Task
        </button>
        <span class="tlp-dropdown-menu-title" role="menuitem">Others</span>
        <button
            type="button"
            class=${getItemClasses(args)}
            ?disabled=${args.disabled_options}
            role="menuitem"
        >
            Epic
        </button>
        <button
            type="button"
            class=${getItemClasses(args)}
            ?disabled=${args.disabled_options}
            role="menuitem"
        >
            Test case
        </button>
        <span class="tlp-dropdown-menu-separator" role="separator"></span>
        <button
            type="button"
            class="${getItemClasses(args)} tlp-dropdown-menu-item-danger"
            ?disabled=${args.disabled_options}
            role="menuitem"
        >
            External request
        </button>`;
}

function getBaseTemplate(args: DropdownProps): TemplateResult {
    const dropdown_items = args.hypertext
        ? getDropdownItemsAsLinks(args)
        : getDropdownItemsAsButtons(args);

    // prettier-ignore
    return html`
<div class="tlp-dropdown">
    <button type="button" id="dropdown-example" class="tlp-button-primary">
        <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>
        Add item
        <i class="fa-solid fa-caret-down tlp-button-icon" aria-hidden="true"></i>
    </button>
    <div class=${getMenuClasses(args)} id="dropdown-menu-example" role="menu">
        ${ dropdown_items }
    </div>
</div>`;
}

function getTemplateWithSubMenu(): TemplateResult {
    // prettier-ignore
    return html`
<button type="button" id="dropdown-example" class="tlp-button-primary tlp-button-outline">
    Using options and submenu
    <i class="fa-solid fa-caret-down tlp-button-icon" aria-hidden="true"></i>
</button>
<!-- Dropdown menu, anywhere in the page -->
<div>
    <div class="tlp-dropdown-menu" id="dropdown-menu-example" role="menu">
        <span class="tlp-dropdown-menu-title" role="menuitem">Favorites</span>
        <div class="tlp-dropdown-menu-item tlp-dropdown-menu-item-submenu"
             id="dropdown-submenu-example-1"
             aria-haspopup="true"
             role="menuitem"
        >
            Create a new awesome item…
            <div class="tlp-dropdown-menu tlp-dropdown-submenu tlp-dropdown-menu-side" id="dropdown-menu-example-submenu-1" role="menu" aria-label="User story">
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Fancy user story</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Task</a>
            </div>
        </div>
        <div class="tlp-dropdown-menu-item tlp-dropdown-menu-item-submenu"
             id="dropdown-submenu-example-2"
             aria-haspopup="true"
             role="menuitem"
        >
            Task
            <div class="tlp-dropdown-menu tlp-dropdown-submenu tlp-dropdown-menu-side" id="dropdown-menu-example-submenu-2" role="menu" aria-label="User story">
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Lorem</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Ipsum</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Doloret</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Sit</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Amet</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Consectetur</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Adipiscing</a>
                <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">Elit</a>
            </div>
        </div>
    </div>
</div>`;
}

function getTemplateWithTabs(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-dropdown">
    <button type="button" id="dropdown-example" class="tlp-button-primary">
        with tabs
        <i class="fa-solid fa-caret-down tlp-button-icon" aria-hidden="true"></i>
    </button>
    <div class="tlp-dropdown-menu tlp-dropdown-with-tabs-on-top" id="dropdown-menu-example" role="menu">
        <nav class="tlp-tabs">
            <button type="button" role="menuitem" class="tlp-tab tlp-tab-active">Branches</button>
            <button type="button" role="menuitem" class="tlp-tab">Tags</button>
        </nav>
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="tlp-dropdown-menu-item-icon fa-brands fa-fw fa-rebel" aria-hidden="true"></i> Send a email to the Rebel Alliance
        </a>
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="tlp-dropdown-menu-item-icon fa-brands fa-fw fa-empire" aria-hidden="true"></i> Send a email to the Empire
        </a>
    </div>
</div>`;
}

function getTemplateWithFilter(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-dropdown">
    <button type="button" id="dropdown-example" class="tlp-button-primary">
        with filter
        <i class="fa-solid fa-caret-down tlp-button-icon" aria-hidden="true"></i>
    </button>
    <div class="tlp-dropdown-menu dropdown-menu-example-filter" id="dropdown-menu-example" role="menu">
        <div class="tlp-dropdown-menu-actions">
            <input type="search" class="tlp-search tlp-search-small" placeholder="Filter…">
            <button class="tlp-button-primary tlp-button-small">
                <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>
                Create new item
            </button>
        </div>
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            Open the last 10 days
        </a>
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            All about me
        </a>
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            Waiting for action
        </a>
    </div>
</div>`;
}

function getTemplateWithRegularSplitButton(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-dropdown">
    <div class="tlp-dropdown-split-button">
        <button class="tlp-button-primary tlp-button-outline tlp-dropdown-split-button-main" type="button"
                title="Passed">
            New document
        </button>
        <button class="tlp-button-primary tlp-button-outline tlp-dropdown-split-button-caret" id="dropdown-example">
            <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
        </button>
    </div>
    <div id="dropdown-menu-example" class="tlp-dropdown-menu" role="menu">
        <a class="tlp-dropdown-menu-item steps-step-action-fail ng-binding" role="menuitem">
            <i class="fa-solid fa-fw fa-circle-xmark tlp-dropdown-menu-item-icon" aria-hidden="true"></i> Failed
        </a>
        <a class="tlp-dropdown-menu-item steps-step-action-block ng-binding" role="menuitem">
            <i class="fa-solid fa-fw fa-circle-exclamation tlp-dropdown-menu-item-icon" aria-hidden="true"></i> Blocked
        </a>
        <a class="tlp-dropdown-menu-item steps-step-action-notrun ng-binding" role="menuitem">
            <i class="fa-solid fa-fw fa-circle-question tlp-dropdown-menu-item-icon" aria-hidden="true"></i> Not run
        </a>
    </div>
</div>`;
}

function getTemplateWithLargeSplitButton(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-dropdown">
    <div class="tlp-dropdown-split-button">
        <button class="tlp-button-primary tlp-dropdown-split-button-main tlp-button-large" type="button" title="Passed">
            New document
        </button>
        <button class="tlp-button-primary tlp-dropdown-split-button-caret tlp-button-large" id="dropdown-example">
            <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
        </button>
    </div>
    <div id="dropdown-menu-example" class="tlp-dropdown-menu tlp-dropdown-menu-large" role="menu">
        <a class="tlp-dropdown-menu-item steps-step-action-fail ng-binding" role="menuitem">
            <i class="fa-solid fa-fw fa-circle-xmark" aria-hidden="true"></i> Failed
        </a>
        <a class="tlp-dropdown-menu-item steps-step-action-block ng-binding" role="menuitem">
            <i class="fa-solid fa-fw fa-circle-exclamation" aria-hidden="true"></i> Blocked
        </a>
        <a class="tlp-dropdown-menu-item steps-step-action-notrun ng-binding" role="menuitem">
            <i class="fa-solid fa-fw fa-circle-question" aria-hidden="true"></i> Not run
        </a>
    </div>
</div>`;
}

function getTemplateWithUniqueIconTrigger(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-dropdown">
    <i class="fa-solid fa-gear fa-fw" aria-hidden="true" id="dropdown-example"></i>
    <div id="dropdown-menu-example" class="tlp-dropdown-menu tlp-dropdown-menu-on-icon" role="menu">
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="tlp-dropdown-menu-item-icon fa-brands fa-fw fa-rebel" aria-hidden="true"></i> Send a email to the Rebel Alliance
        </a>
        <a href="https://example.com" class="tlp-dropdown-menu-item" role="menuitem">
            <i class="tlp-dropdown-menu-item-icon fa-brands fa-fw fa-empire" aria-hidden="true"></i> Send a email to the Empire
        </a>
    </div>
</div>`;
}

function getTemplate(args: DropdownProps): TemplateResult {
    switch (args.story) {
        case "with submenu":
            return getTemplateWithSubMenu();
        case "with tabs":
            return getTemplateWithTabs();
        case "with filter":
            return getTemplateWithFilter();
        case "split button":
            return getTemplateWithRegularSplitButton();
        case "large split button":
            return getTemplateWithLargeSplitButton();
        case "unique icon trigger":
            return getTemplateWithUniqueIconTrigger();
        case "base":
        default:
            return getBaseTemplate(args);
    }
}

const meta: Meta<DropdownProps> = {
    title: "TLP/Fly Over/Dropdowns",
    parameters: {
        controls: {
            exclude: ["story"],
        },
    },
    render: (args: DropdownProps) => {
        return getTemplate(args);
    },
    args: {
        hypertext: true,
        story: "base",
        top_align: false,
        disabled_options: false,
        trigger: "click",
        keyboard: true,
    },
    argTypes: {
        hypertext: {
            name: "Hypertext",
            description:
                "When true, the dropdown items are &lt;a&gt; elements. When false, the dropdown items are &lt;button&gt; elements.",
        },
        top_align: {
            name: "Top align",
            description: "Add the class",
            table: {
                type: { summary: ".tlp-dropdown-menu-top" },
            },
            control: "boolean",
        },
        disabled_options: {
            name: "Disabled options",
            description: "Sets the disabled attribute or add the class",
            table: {
                type: { summary: ".tlp-dropdown-menu-item-disabled" },
            },
        },
        trigger: {
            control: "select",
            options: ["click", "hover-and-click"],
            description: "If 'hover-and-click', dropdown is also displayed on mouseover",
            table: {
                defaultValue: { summary: "click" },
            },
        },
        keyboard: {
            description: "If 'false', Disable closing the dropdown on the escape key pressed",
            table: {
                defaultValue: { summary: "true" },
            },
        },
    },
    decorators: [
        (story, { args }: { args: DropdownProps }): TemplateResult =>
            html`<div class=${args.top_align ? "dropdown-wrapper-top-align" : "dropdown-wrapper"}>
                <tuleap-dropdown-wrapper
                    ?submenu=${args.story === "with submenu"}
                    .trigger=${args.trigger}
                    .keyboard=${args.keyboard}
                    >${story()}</tuleap-dropdown-wrapper
                >
            </div>`,
    ],
};
export default meta;
type Story = StoryObj<DropdownProps>;

export const Dropdown: Story = {};

export const WithSubmenu: Story = {
    args: {
        story: "with submenu",
    },
};

export const WithTab: Story = {
    args: {
        story: "with tabs",
    },
};

export const WithFilter: Story = {
    args: {
        story: "with filter",
    },
};
export const SplitButton: Story = {
    args: {
        story: "split button",
    },
};

export const LargeSplitButton: Story = {
    args: {
        story: "large split button",
    },
};

export const UniqueIconTrigger: Story = {
    args: {
        story: "unique icon trigger",
    },
};
