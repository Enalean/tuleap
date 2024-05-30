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

import type { Meta, StoryObj } from "@storybook/web-components";
import { html, type TemplateResult } from "lit";

type TabsProps = {
    vertical: boolean;
    active: boolean;
    disabled: boolean;
    with_badge: boolean;
    with_icon: boolean;
    with_menu: boolean;
};

function getTabsClass(args: TabsProps): string {
    let tabs_class = "tlp-tabs";
    if (args.vertical) {
        tabs_class += "-vertical";
    }
    return tabs_class;
}

function getTabClasses(args: TabsProps): string {
    const tab_classes = ["tlp-tab"];
    if (args.disabled) {
        tab_classes.push("tlp-tab-disabled");
    } else if (args.active) {
        tab_classes.push(`tlp-tab-active`);
    }
    return tab_classes.join(" ");
}

function getTemplate(args: TabsProps): TemplateResult {
    // prettier-ignore
    return html`
<nav class="${getTabsClass(args)}">
    <a class="tlp-tab">First tab</a>
    <a class="${getTabClasses(args)}">${args.with_icon ? html`
        <i class="tlp-tab-icon fa-solid fa-tlp-tuleap " aria-hidden="true"></i>` : ``}
        Custom tab ${args.with_badge ? html`
        <span class="tlp-tab-badge-append tlp-badge-primary tlp-badge-outline">3</span>` : ``}${args.with_menu ? html`
        <i class="fa-solid fa-caret-down" aria-hidden="true"></i>
        <nav class="tlp-tab-menu">
            <span class="tlp-tab-menu-title">Exports</span>
            <a class="tlp-tab-menu-item">CSV</a>
            <a class="tlp-tab-menu-item">Excel (2010 version)</a>
            <a class="tlp-tab-menu-item">PDF (Acrobat compat)</a>
            <span class="tlp-tab-menu-title">Imports</span>
            <a class="tlp-tab-menu-item">CSV</a>
            <hr class="tlp-tab-menu-separator" />
            <a class="tlp-tab-menu-item tlp-text-danger">Delete</a>
        </nav>` : ``}
    </a>
    <a class="tlp-tab">Another one</a>
</nav>`;
}

const meta: Meta<TabsProps> = {
    title: "TLP/Structure & Navigation/Tabs",
    parameters: {
        layout: "centered",
    },
    render: (args: TabsProps) => {
        return getTemplate(args);
    },
    argTypes: {
        vertical: {
            name: "Vertical",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-tabs-vertical" },
            },
        },
        active: {
            name: "Active",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-tab-active" },
            },
        },
        disabled: {
            name: "Disabled",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-tab-disabled" },
            },
        },
        with_badge: {
            name: "With badge",
            description: "Add a badge with the appropriate class",
            table: {
                type: { summary: ".tlp-tab-badge-append" },
            },
        },
        with_icon: {
            name: "With icon",
            description: "Add an icon with the appropriate class",
            table: {
                type: { summary: ".tlp-tab-icon" },
            },
        },
        with_menu: {
            name: "With menu",
            description: "Add an example of menu with the appropriate class",
            table: {
                type: { summary: ".tlp-tab-menu" },
            },
        },
    },
    args: {
        vertical: false,
        active: true,
        disabled: false,
        with_badge: false,
        with_icon: false,
        with_menu: false,
    },
};
export default meta;
type Story = StoryObj;

export const Tabs: Story = {};
