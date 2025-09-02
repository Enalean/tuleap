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

type InputProps = {
    label: string;
    placeholder: string;
    size: string;
    with_size_attribute: boolean;
    size_attribute: number;
    mandatory: boolean;
    disabled: boolean;
    with_helper_text: boolean;
    with_error: boolean;
};

const INPUT_SIZES = ["default", "large", "small"];

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

function getFormClasses(args: InputProps): string {
    const classes = [`tlp-form-element`];
    if (args.with_error) {
        classes.push(`tlp-form-element-error`);
    }
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getInputClasses(args: InputProps): string {
    const classes = [`tlp-input`];
    if (args.size !== "default") {
        classes.push(`tlp-input-${args.size}`);
    }
    return classes.join(" ");
}

function getInput(args: InputProps): TemplateResult {
    if (args.size_attribute) {
        // prettier-ignore
        return html`
    <input type="text" class=${getInputClasses(args)} id="input-example" name="username" size=${args.size_attribute} placeholder=${args.placeholder} ?disabled=${args.disabled}>`;
    }
    // prettier-ignore
    return html`
    <input type="text" class=${getInputClasses(args)} id="input-example" name="username" placeholder=${args.placeholder} ?disabled=${args.disabled}>`;
}

function getTemplate(args: InputProps): TemplateResult {
    //prettier-ignore
    return html`
<div class=${getFormClasses(args)}>
    <label class="tlp-label" for="input-example">${args.label} ${args.mandatory ? asterisk : ``}</label>${getInput(args)}${args.with_helper_text ? helper_text : ``}${args.with_error ? error : ``}
</div>`;
}

const meta: Meta<InputProps> = {
    title: "TLP/Forms/Inputs",
    parameters: {
        layout: "padded",
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        label: "Username",
        placeholder: "fox_mulder",
        size: "default",
        with_size_attribute: false,
        size_attribute: 4,
        mandatory: false,
        disabled: false,
        with_helper_text: false,
        with_error: false,
    },
    argTypes: {
        label: {
            name: "Label",
        },
        placeholder: {
            name: "Placeholder",
        },
        size: {
            name: "Size",
            description: "Input size, applies the class",
            control: "select",
            options: INPUT_SIZES,
            table: {
                type: { summary: ".tlp-input-[size]" },
            },
        },
        with_size_attribute: {
            name: "With size attribute",
            description: "Add size attribute",
            table: {
                type: { summary: undefined },
            },
        },
        size_attribute: {
            name: "Size attribute value",
            table: {
                type: { summary: undefined },
            },
            if: { arg: "with_size_attribute" },
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
type Story = StoryObj<InputProps>;

export const Input: Story = {};
