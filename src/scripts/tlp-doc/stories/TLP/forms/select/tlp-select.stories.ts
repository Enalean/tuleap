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

type SelectProps = {
    label: string;
    size: string;
    multiple: boolean;
    adjusted: boolean;
    mandatory: boolean;
    disabled: boolean;
    with_helper_text: boolean;
    with_error: boolean;
};

const SELECT_SIZES = ["default", "large", "small"];

// prettier-ignore
const helper_text: TemplateResult = html`
    <p class="tlp-text-info">
        <i class="fa-regular fa-life-ring" aria-hidden="true"></i>
        Hey bro', I'm a help text. I'm here to help you.
    </p>`;

// prettier-ignore
const error: TemplateResult = html`
    <p class="tlp-text-danger">
        Oops, it seems you missed something
        <i class="fa-regular fa-face-frown" aria-hidden="true"></i>
    </p>`;

// prettier-ignore
const asterisk: TemplateResult = html`<i class="fa-solid fa-asterisk" aria-hidden="true"></i>`;

function getFormClasses(args: SelectProps): string {
    const classes = [`tlp-form-element`];
    if (args.with_error) {
        classes.push(`tlp-form-element-error`);
    }
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getSelectClasses(args: SelectProps): string {
    const classes = [`tlp-select`];
    if (args.size !== "default") {
        classes.push(`tlp-select-${args.size}`);
    }
    if (args.adjusted) {
        classes.push(`tlp-select-adjusted`);
    }
    return classes.join(" ");
}

function getTemplate(args: SelectProps): TemplateResult {
    // prettier-ignore
    return html`
<div class=${getFormClasses(args)}>
    <label class="tlp-label" for="option">${args.label} ${args.mandatory ? asterisk : ``}</label>
    <select class=${getSelectClasses(args)} id="option" name="option" ?multiple=${args.multiple} ?disabled=${args.disabled}>
        <option value=""></option>
        <option value="option-1">Option 1</option>
        <option value="option-2">Option 2</option>
        <option value="option-3">Option 3</option>
        <option value="option-4">Option 4</option>
    </select>${args.with_helper_text ? helper_text : ``}${args.with_error ? error : ``}
</div>`;
}

const meta: Meta<SelectProps> = {
    title: "TLP/Forms/Selects",
    parameters: {
        layout: "padded",
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        label: "Option",
        size: "default",
        multiple: false,
        adjusted: false,
        mandatory: false,
        disabled: false,
        with_helper_text: false,
        with_error: false,
    },
    argTypes: {
        label: {
            name: "Label",
        },
        size: {
            name: "Size",
            description: "Select size, applies the class",
            control: "select",
            options: SELECT_SIZES,
            table: {
                type: { summary: ".tlp-select-[size]" },
            },
        },
        multiple: {
            name: "Multiple",
            description: "To have a multiselect, add multiple attribute",
        },
        adjusted: {
            name: "Adjusted",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-select-adjusted" },
            },
        },
        mandatory: {
            name: "Mandatory",
            table: {
                type: { summary: undefined },
            },
        },
        disabled: {
            name: "Disabled",
            description: "Add disabled attribute and applies the class",
            table: {
                type: { summary: ".tlp-form-element-disabled" },
            },
        },
        with_helper_text: {
            name: "With helper text",
            table: {
                type: { summary: undefined },
            },
            if: { arg: "with_error", truthy: false },
        },
        with_error: {
            name: "With error",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-form-element-error" },
            },
        },
    },
};

export default meta;
type Story = StoryObj<SelectProps>;

export const Select: Story = {};
