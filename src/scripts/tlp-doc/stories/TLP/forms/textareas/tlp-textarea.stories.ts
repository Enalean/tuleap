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

type TextareaProps = {
    label: string;
    placeholder: string;
    size: string;
    with_given_dimensions: boolean;
    cols: number;
    rows: number;
    mandatory: boolean;
    disabled: boolean;
    with_helper_text: boolean;
    with_error: boolean;
};

const TEXTAREA_SIZES = ["default", "large", "small"];

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

function getFormClasses(args: TextareaProps): string {
    const classes = [`tlp-form-element`];
    if (args.with_error) {
        classes.push(`tlp-form-element-error`);
    }
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getTextareaClasses(args: TextareaProps): string {
    const classes = [`tlp-textarea`];
    if (args.size !== "default") {
        classes.push(`tlp-textarea-${args.size}`);
    }
    return classes.join(" ");
}

function getTextarea(args: TextareaProps): TemplateResult {
    if (args.with_given_dimensions) {
        // prettier-ignore
        return html`
    <textarea class=${getTextareaClasses(args)} id="textarea-example" name="commit-message" cols=${args.cols} rows=${args.rows} placeholder=${args.placeholder} ?disabled=${args.disabled}></textarea>`;
    }
    // prettier-ignore
    return html`
    <textarea class=${getTextareaClasses(args)} id="textarea-example" name="commit-message" placeholder=${args.placeholder} ?disabled=${args.disabled}></textarea>`;
}

function getTemplate(args: TextareaProps): TemplateResult {
    //prettier-ignore
    return html`
<div class=${getFormClasses(args)}>
    <label class="tlp-label" for="textarea-example">${args.label} ${args.mandatory ? asterisk : ``}</label>${getTextarea(args)}${args.with_helper_text ? helper_text : ``}${args.with_error ? error : ``}
</div>`;
}

const meta: Meta<TextareaProps> = {
    title: "TLP/Forms/Textarea",
    parameters: {
        layout: "padded",
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        label: "Description",
        placeholder: "I want to believe",
        size: "default",
        with_given_dimensions: false,
        cols: 60,
        rows: 10,
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
            description: "Textarea size, applies the class",
            control: "select",
            options: TEXTAREA_SIZES,
            table: {
                type: { summary: ".tlp-textarea-[size]" },
            },
        },
        with_given_dimensions: {
            name: "With given dimensions",
            description: "Add cols and rows attributes",
            table: {
                type: { summary: undefined },
            },
        },
        cols: {
            name: "Cols",
            if: { arg: "with_given_dimensions" },
        },
        rows: {
            name: "Rows",
            if: { arg: "with_given_dimensions" },
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
type Story = StoryObj<TextareaProps>;

export const Textarea: Story = {};
