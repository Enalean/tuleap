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

type PrependProps = {
    story: "icon" | "multiple-prepends" | "with-badge";
    size: string;
    placeholder: string;
    with_label: boolean;
    label: string;
};

const SIZES = ["default", "large", "small"];

function getClasses(args: PrependProps, element: string): string {
    const classes = [`tlp-${element}`];
    if (args.size !== "default") {
        classes.push(`tlp-${element}-${args.size}`);
    }
    return classes.join(" ");
}

function getPrepends(args: PrependProps): TemplateResult {
    // prettier-ignore
    const prepend: TemplateResult = !args.with_label ? html`
    <span class=${getClasses(args, "prepend")}>
        <i class="fa-solid fa-tags" aria-hidden="true"></i>
    </span>` : html`
        <span class=${getClasses(args, "prepend")}>
            <i class="fa-solid fa-tags" aria-hidden="true"></i>
        </span>`;
    if (args.story === "with-badge") {
        // prettier-ignore
        return html`${prepend}
    <span class=${getClasses(args, "prepend")}>
        <span class="tlp-badge-primary">Read-only</span>
    </span>`;
    }
    if (args.story === "multiple-prepends") {
        // prettier-ignore
        return html`${prepend}
    <span class=${getClasses(args, "prepend")}>https://</span>`;
    }
    return prepend;
}

function getTemplate(args: PrependProps): TemplateResult {
    if (args.with_label) {
        //prettier-ignore
        return html`
<div class="tlp-form-element">
    <label class="tlp-label" for=${args.story}>${args.label}</label>
    <div class="tlp-form-element tlp-form-element-prepend">${getPrepends(args)}
        <input type="text" class=${getClasses(args, "input")} id="${args.story}" name="${args.story}" placeholder=${args.placeholder}>
    </div>
</div>`;
    }
    //prettier-ignore
    return html`
<div class="tlp-form-element tlp-form-element-prepend">${getPrepends(args)}
    <input type="text" class=${getClasses(args, "input")} id="${args.story}" name="${args.story}" placeholder=${args.placeholder}>
</div>`;
}

const meta: Meta<PrependProps> = {
    title: "TLP/Forms/Prepends",
    parameters: {
        controls: {
            exclude: ["story"],
        },
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        placeholder: "placeholder",
        size: "default",
        with_label: false,
        label: "Tags",
    },
    argTypes: {
        placeholder: {
            name: "Placeholder",
        },
        size: {
            name: "Size",
            description: "Applies the classes",
            control: "select",
            options: SIZES,
            table: {
                type: { summary: ".tlp-input-[size] .tlp-prepend-[size]" },
            },
        },
        with_label: {
            name: "With label",
            description: "Add a label",
            table: {
                type: { summary: undefined },
            },
        },
        label: {
            name: "Label",
            if: { arg: "with_label" },
        },
    },
};

export default meta;
type Story = StoryObj<PrependProps>;

export const WithIcon: Story = {
    args: {
        story: "icon",
    },
};

export const MultiplePrepends: Story = {
    args: {
        story: "multiple-prepends",
    },
};

export const WithBadge: Story = {
    args: {
        story: "with-badge",
    },
};
