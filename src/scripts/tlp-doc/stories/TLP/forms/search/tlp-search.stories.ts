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
import type { TemplateResult } from "lit";
import { html } from "lit";

type SearchProps = {
    placeholder: string;
    size: string;
    disabled: boolean;
};

const SEARCH_SIZES = ["default", "large", "small"];

function getFormClasses(args: SearchProps): string {
    const classes = [`tlp-form-element`];
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getSearchClasses(args: SearchProps): string {
    const classes = [`tlp-search`];
    if (args.size !== "default") {
        classes.push(`tlp-search-${args.size}`);
    }
    return classes.join(" ");
}

function getTemplate(args: SearchProps): TemplateResult {
    //prettier-ignore
    return html`
<div class=${getFormClasses(args)}>
    <input type="search" class=${getSearchClasses(args)} placeholder="${args.placeholder}" ?disabled=${args.disabled}>
</div>`;
}

const meta: Meta<SearchProps> = {
    title: "TLP/Forms/Search",
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        placeholder: "People, project…",
        size: "default",
        disabled: false,
    },
    argTypes: {
        placeholder: {
            name: "Placeholder",
        },
        size: {
            name: "Size",
            description: "Search size, applies the class",
            control: "select",
            options: SEARCH_SIZES,
            table: {
                type: { summary: ".tlp-search-[size]" },
            },
        },
        disabled: {
            name: "Disabled",
            description: "Add disabled attribute and applies the class",
            table: {
                type: { summary: ".tlp-form-element-disabled" },
            },
        },
    },
};

export default meta;
type Story = StoryObj<SearchProps>;

export const Search: Story = {};
