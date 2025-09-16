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

type SwitchProps = {
    label_placement: "none" | "top" | "right";
    form_label: string;
    size: "default" | "large" | "mini";
    disabled: boolean;
    state: "checked" | "unchecked" | "loading";
};

const LABEL_PLACEMENTS = ["none", "top", "right"];
const SWITCH_SIZES = ["default", "large", "mini"];
const SWITCH_STATES = ["checked", "unchecked", "loading"];

function getSizeClasses(args: SwitchProps): string {
    const classes = ["tlp-switch"];
    if (args.size !== "default") {
        classes.push(`tlp-switch-${args.size}`);
    }
    return classes.join(" ");
}

function getButtonClasses(args: SwitchProps): string {
    const classes = ["tlp-switch-button"];
    if (args.state === "loading") {
        classes.push("loading");
    }
    return classes.join(" ");
}

const isChecked = (args: SwitchProps): boolean => args.state === "checked";

function getTemplate(args: SwitchProps): TemplateResult {
    if (args.label_placement === "top") {
        //prettier-ignore
        return html`
<div class="tlp-form-element">
    <label class="tlp-label" for="toggle">${args.form_label}</label>
    <div class=${getSizeClasses(args)}>
        <input type="checkbox" id="toggle" class="tlp-switch-checkbox" ?disabled=${args.disabled} ?checked=${isChecked(args)}>
        <label for="toggle" class=${getButtonClasses(args)}></label>
    </div>
</div>`;
    }
    if (args.label_placement === "right") {
        //prettier-ignore
        return html`
<div class="tlp-switch-with-label-on-right">
    <div class=${getSizeClasses(args)}>
        <input type="checkbox" id="toggle" class="tlp-switch-checkbox" ?disabled=${args.disabled} ?checked=${isChecked(args)}>
        <label for="toggle" class=${getButtonClasses(args)}></label>
    </div>
    <label class="tlp-label" for="toggle">${args.form_label}</label>
</div>
        `;
    }
    //prettier-ignore
    return html`
<div class=${getSizeClasses(args)}>
    <input type="checkbox" id="toggle" class="tlp-switch-checkbox" ?disabled=${args.disabled} ?checked=${isChecked(args)}>
    <label for="toggle" class=${getButtonClasses(args)}></label>
</div>`;
}

const meta: Meta<SwitchProps> = {
    title: "TLP/Buttons & Switch/Switch",
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        label_placement: "none",
        form_label: "Activate advanced mode",
        size: "default",
        disabled: false,
        state: "checked",
    },
    argTypes: {
        label_placement: {
            name: "Label Placement",
            description: `Where the label of the switch should be. "none" removes it`,
            control: "select",
            options: LABEL_PLACEMENTS,
            table: { type: { summary: undefined } },
        },
        form_label: {
            name: "Form label",
            if: { arg: "label_placement", neq: "none" },
        },
        size: {
            name: "Size",
            description: "Switch size, applies the class",
            control: "select",
            options: SWITCH_SIZES,
            table: {
                type: { summary: ".tlp-switch-[size]" },
            },
        },
        disabled: {
            name: "Disabled",
            description: "Add disabled attribute.",
            table: {
                type: { summary: undefined },
            },
        },
        state: {
            name: "State",
            description: `The current state of the switch. When "loading", applies the class`,
            control: "select",
            options: SWITCH_STATES,
            table: {
                type: { summary: ".loading" },
            },
        },
    },
};

export default meta;
type Story = StoryObj<SwitchProps>;

export const Switch: Story = {};
