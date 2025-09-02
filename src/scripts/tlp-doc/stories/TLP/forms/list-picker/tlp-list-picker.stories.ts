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
import type { ListPickerOptions } from "@tuleap/list-picker";
import "@tuleap/list-picker";
import "./ListPickerWrapper";
import "@tuleap/list-picker/style.css";

// prettier-ignore
const helper_text: TemplateResult = html`
    <p class="tlp-text-info">
        <i class="fa-regular fa-life-ring" aria-hidden="true"></i>
        Hey bro', I'm a help text. I'm here to help you.
    </p>`;

// prettier-ignore
const error: TemplateResult = html`
    <p class="tlp-text-danger">
        This field is mandatory, please choose a value in the list.
    </p>`;

// prettier-ignore
const asterisk: TemplateResult = html`<i class="fa-solid fa-asterisk" aria-hidden="true"></i>`;

type ListPickerProps = {
    story: "list-picker" | "list-picker-multiple" | "with-custom-items-template";
    locale: string;
    placeholder: string;
    is_filterable: boolean;
    none_value: string | null;
    with_opt_group: boolean;
    mandatory: boolean;
    disabled: boolean;
    with_helper_text: boolean;
    with_error: boolean;
    items_template_formatter: ListPickerOptions["items_template_formatter"] | undefined;
    multiple: boolean;
};

function getFormClasses(args: ListPickerProps): string {
    const classes = [`tlp-form-element`];
    if (args.with_error) {
        classes.push(`tlp-form-element-error`);
    }
    if (args.disabled) {
        classes.push(`tlp-form-element-disabled`);
    }
    return classes.join(" ");
}

function getOptions(args: ListPickerProps): TemplateResult {
    if (args.story === "with-custom-items-template") {
        // prettier-ignore
        return html`
        <option value=""></option>
        <optgroup label="Lannisters">
            <option value="101" selected>Tyrion</option>
            <option value="102">Jaime</option>
            <option value="103" disabled>Tywin</option>
            <option value="104">Cersei</option>
        </optgroup>
        <optgroup label="Stark">
            <option value="105">Sansa</option>
            <option value="106" selected>Arya</option>
            <option value="107">Bran</option>
            <option value="108" disabled>Eddard</option>
        </optgroup>
    `;
    }
    if (args.with_opt_group) {
        //prettier-ignore
        return html`
        <option value=""></option>
        <optgroup label="Options">
            <option value="1">option 1</option>
            <option value="2">option 2</option>
            <option value="3">option 3</option>
            <option value="4">option 4</option>
            <option value="5">option 5</option>
            <option value="6">option 6</option>
        </optgroup>
    `;
    }
    //prettier-ignore
    return html`
        <option value=""></option>
        <option value="1">option 1</option>
        <option value="2">option 2</option>
        <option value="3">option 3</option>
        <option value="4">option 4</option>
        <option value="5">option 5</option>
        <option value="6">option 6</option>
    `;
}

function getTemplate(args: ListPickerProps): TemplateResult {
    //prettier-ignore
    return html`
<div class=${getFormClasses(args)}>
    <label class="tlp-label" for="${args.story}-select">ListPicker ${args.mandatory ? asterisk : ``}</label>
    <select id="${args.story}-select" name="list-value" ?disabled=${args.disabled} ?multiple=${args.multiple}>${getOptions(args)}</select>${args.with_helper_text ? helper_text : ``}${args.with_error ? error : ``}
</div>`;
}

const meta: Meta<ListPickerProps> = {
    title: "TLP/Forms/List picker",
    parameters: {
        layout: "padded",
        controls: {
            exclude: ["story", "multiple"],
        },
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        locale: "en_US",
        placeholder: "Choose an option",
        is_filterable: false,
        none_value: null,
        mandatory: false,
        disabled: false,
        with_helper_text: false,
        with_error: false,
        multiple: false,
    },
    argTypes: {
        locale: {
            description:
                "A locale string formatted with underscores. For example: 'en_US', 'fr_FR'. It is used to localize List Picker. If undefined, it will fallback to 'en_US'",
            control: "select",
            options: ["en_US", "fr_FR"],
            table: {
                type: { summary: "string | undefined" },
                defaultValue: { summary: "undefined" },
            },
        },
        placeholder: {
            description: "A (translated) text inviting the user to fill up the field",
            control: "text",
            table: { defaultValue: { summary: "" } },
        },
        is_filterable: {
            description: "Add a search input in the list picker dropdown",
            control: "boolean",
            table: { defaultValue: { summary: "false" } },
        },
        none_value: {
            description:
                "The none value given on the select. If not null, the option with this value will be automatically selected when no other value is selected. It will also be removed from the selection when any other value is selected",
            control: false,
            table: {
                type: { summary: "string | null" },
                defaultValue: { summary: "null" },
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
    decorators: [
        (story, { args }: { args: ListPickerProps }): TemplateResult =>
            html`<tuleap-list-picker-wrapper
                locale=${args.locale}
                placeholder=${args.placeholder}
                ?filterable=${args.is_filterable}
                .items_template_formatter=${args.items_template_formatter}
                >${story()}</tuleap-list-picker-wrapper
            >`,
    ],
};

export default meta;
type Story = StoryObj<ListPickerProps>;

export const ListPicker: Story = {
    args: {
        story: "list-picker",
        with_opt_group: false,
    },
    argTypes: {
        with_opt_group: {
            name: "With grouped options",
            description: "Add a &lt;optgroup&gt; tag to group options",
            table: {
                type: { summary: undefined },
            },
        },
    },
};

export const ListPickerMultiple: Story = {
    args: {
        story: "list-picker-multiple",
        multiple: true,
    },
};

export const WithCustomItemsTemplate: Story = {
    args: {
        story: "with-custom-items-template",
        placeholder: "Choose GoT characters",
        items_template_formatter: (html, value_id, option_label) => {
            if (value_id === "103" || value_id === "108") {
                return html`<i class="fa-solid fa-fw fa-user-slash" aria-hidden="true"></i>
                    ${option_label}`;
            }
            return html`<i class="fa-solid fa-fw fa-user" aria-hidden="true"></i> ${option_label}`;
        },
    },
};
