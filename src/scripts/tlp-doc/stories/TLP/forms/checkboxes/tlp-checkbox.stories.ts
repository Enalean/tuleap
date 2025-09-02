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

type CheckboxProps = {
    checkbox_label?: string;
    mandatory?: boolean;
    disabled?: boolean;
    with_helper_text?: boolean;
    with_error?: boolean;
    with_form_label?: boolean;
    form_label?: string;
    checked?: boolean;
    story?: "one_form_element" | "many_form_elements";
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

// prettier-ignore
const asterisk: TemplateResult = html`<i class="fa-solid fa-asterisk" aria-hidden="true"></i>`;

function getFormLabel(args: CheckboxProps): TemplateResult {
    if (args.story === "one_form_element") {
        // prettier-ignore
        return html`
    <label class="tlp-label">Select your favorite fruits</label>`;
    }
    if (args.with_form_label) {
        // prettier-ignore
        return html`
    <label class="tlp-label">${args.form_label}</label>`;
    }
    return html``;
}

function getFormClasses(args: CheckboxProps): string {
    const classes = [`tlp-form-element`];
    if (args.with_error) {
        classes.push(`tlp-form-element-error`);
    }
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getCheckboxes(args: CheckboxProps): TemplateResult {
    if (args.story === "one_form_element") {
        // prettier-ignore
        return html`
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" name="apple" value="1"> Apple
    </label>
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" name="banana" value="1"> Banana
    </label>
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" name="pear" value="1"> pear
    </label>`
    }
    // prettier-ignore
    return html`
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" name="subscribe" value="1" ?disabled=${args.disabled} ?checked=${args.checked} ?required=${args.mandatory}>
        ${args.checkbox_label} ${args.mandatory ? asterisk : ``}
    </label>${args.with_helper_text ? helper_text : ``}${args.with_error ? error : ``}`
}

function getTemplate(args: CheckboxProps): TemplateResult {
    if (args.story === "many_form_elements") {
        // prettier-ignore
        return html`
<div class=${getFormClasses(args)}>
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" name="subscribe" value="1">
        Subscribe to mailing list
    </label>
</div>
<div class=${getFormClasses(args)}>
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" name="terms" value="1">
        I accept the term of services
    </label>
</div>`
    }
    //prettier-ignore
    return html`
<div class=${getFormClasses(args)}>${getFormLabel(args)} ${getCheckboxes(args)}
</div>`;
}

const meta: Meta<CheckboxProps> = {
    title: "TLP/Forms/Checkboxes",
    parameters: {
        controls: {
            exclude: ["story"],
        },
    },
    render: (args) => {
        return getTemplate(args);
    },
};

export default meta;
type Story = StoryObj<CheckboxProps>;

export const Checkbox: Story = {
    args: {
        checkbox_label: "Subscribe to UFO news",
        mandatory: false,
        disabled: false,
        with_helper_text: false,
        with_error: false,
        with_form_label: false,
        form_label: "Newsletter",
        checked: true,
    },
    argTypes: {
        checkbox_label: {
            name: "Checkbox label",
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
        with_form_label: {
            name: "With form element label",
            description: "Add a label to the form element",
            table: {
                type: { summary: undefined },
            },
        },
        form_label: {
            name: "Form element label",
            if: { arg: "with_form_label" },
        },
        checked: {
            name: "Checked",
            description: "Add checked attribute",
            table: {
                type: { summary: undefined },
            },
        },
    },
};

export const OneFormElement: Story = {
    args: {
        story: "one_form_element",
    },
};

export const ManyFormElements: Story = {
    args: {
        story: "many_form_elements",
    },
};
