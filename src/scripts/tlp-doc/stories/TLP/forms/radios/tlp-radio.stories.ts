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

type RadioProps = {
    first_label: string;
    second_label: string;
    mandatory: boolean;
    disabled: boolean;
    with_helper_text: boolean;
    with_error: boolean;
    with_form_label: boolean;
    form_label: string;
};

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

function getFormLabel(args: RadioProps): TemplateResult {
    if (!args.with_form_label) {
        return html``;
    }
    //prettier-ignore
    return html`
    <label class="tlp-label">${args.form_label}</label>`;
}

function getFormClasses(args: RadioProps): string {
    const classes = [`tlp-form-element`];
    if (args.with_error) {
        classes.push(`tlp-form-element-error`);
    }
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getTemplate(args: RadioProps): TemplateResult {
    //prettier-ignore
    return html`
<div class=${getFormClasses(args)}>${getFormLabel(args)}
    <label class="tlp-label tlp-radio">
        <input type="radio" name="shape" value="square" ?disabled=${args.disabled}> ${args.first_label}
    </label>
    <label class="tlp-label tlp-radio">
        <input type="radio" name="shape" value="triangle" ?disabled=${args.disabled}> ${args.second_label}
    </label>${args.with_helper_text ? helper_text : ``}${args.with_error ? error : ``}
</div>`;
}

const meta: Meta<RadioProps> = {
    title: "TLP/Forms/Radios",
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        first_label: "Square",
        second_label: "Triangle",
        disabled: false,
        with_helper_text: false,
        with_error: false,
        with_form_label: false,
        form_label: "Shape",
    },
    argTypes: {
        first_label: {
            name: "First label",
        },
        second_label: {
            name: "Second label",
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
        with_form_label: {
            name: "With form label",
            description: "Add a label to the form",
            table: {
                type: { summary: undefined },
            },
        },
        form_label: {
            name: "Form label",
            if: { arg: "with_form_label" },
        },
    },
};

export default meta;
type Story = StoryObj<RadioProps>;

export const Radio: Story = {};
