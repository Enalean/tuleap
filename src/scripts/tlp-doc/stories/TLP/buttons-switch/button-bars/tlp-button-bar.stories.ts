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
import type { TemplateResult } from "lit";
import { html } from "lit";
import { USER_INTERFACE_EMPHASIS_COLORS } from "@tuleap/core-constants";
import type { UserInterfaceEmphasisColorName } from "@tuleap/core-constants";
import "@tuleap/tlp-styles/components/buttons.scss";

type ButtonBarProps = {
    base: string;
    type: UserInterfaceEmphasisColorName;
    size: string;
    outline: boolean;
    disabled: boolean;
};

const BUTTON_SIZES = ["default", "large", "small", "mini"];

function getClasses(args: ButtonBarProps): string {
    const classes = [`tlp-button-${args.type}`];
    if (args.outline) {
        classes.push("tlp-button-outline");
    }
    if (args.size !== "default") {
        classes.push(`tlp-button-${args.size}`);
    }
    if (args.disabled) {
        classes.push("disabled");
    }
    return classes.join(" ");
}

function getTemplate(args: ButtonBarProps): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-form-element">
    <label class="tlp-label">Formula (based on input <code>type="${args.base}"</code>)</label>
    <div class="tlp-button-bar">
        <div class="tlp-button-bar-item">
            <input type=${args.base} id="button-bar-left" name="button-bar-${args.base}" class="tlp-button-bar-checkbox" checked ?disabled=${args.disabled}>
            <label for="button-bar-left" class=${getClasses(args)}>
                <i class="fa-solid fa-align-left tlp-button-icon" aria-hidden="true"></i> Left
            </label>
        </div>
        <div class="tlp-button-bar-item">
            <input type=${args.base} id="button-bar-center" name="button-bar-${args.base}" class="tlp-button-bar-checkbox" ?disabled=${args.disabled}>
            <label for="button-bar-center" class=${getClasses(args)}>
                <i class="fa-solid fa-align-center tlp-button-icon" aria-hidden="true"></i> Center
            </label>
        </div>
        <div class="tlp-button-bar-item">
            <input type=${args.base} id="button-bar-right" name="button-bar-${args.base}" class="tlp-button-bar-checkbox" ?disabled=${args.disabled}>
            <label for="button-bar-right" class=${getClasses(args)}>
                <i class="fa-solid fa-align-right tlp-button-icon" aria-hidden="true"></i> Right
            </label>
        </div>
        <div class="tlp-button-bar-item">
            <input type=${args.base} id="button-bar-justify" name="button-bar-${args.base}" class="tlp-button-bar-checkbox" ?disabled=${args.disabled}>
            <label for="button-bar-justify" class=${getClasses(args)}>
                <i class="fa-solid fa-align-justify tlp-button-icon" aria-hidden="true"></i> Justify
            </label>
        </div>
    </div>
</div>`;
}

const meta: Meta<ButtonBarProps> = {
    title: "TLP/Buttons & Switch/Button Bars",
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        base: "radio",
        type: "primary",
        size: "default",
        outline: false,
        disabled: false,
    },
    argTypes: {
        base: {
            name: "Base",
            description: "Choose to base your Button bar on radios or checkboxes",
            control: "select",
            options: ["radio", "checkbox"],
            table: {
                type: { summary: undefined },
            },
        },
        type: {
            name: "Type",
            description: "UI color of the buttons",
            control: "select",
            options: USER_INTERFACE_EMPHASIS_COLORS,
        },
        size: {
            name: "Size",
            description: "Button size, applies the class",
            control: "select",
            options: BUTTON_SIZES,
            table: {
                type: { summary: ".tlp-button-[size]" },
            },
        },
        outline: {
            name: "Outline",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-button-outline" },
            },
        },
        disabled: {
            name: "Disabled",
            description: "Add disabled attribute. On label, applies the class",
            table: {
                type: { summary: ".disabled" },
            },
        },
    },
};

export default meta;
type Story = StoryObj<ButtonBarProps>;

export const ButtonBar: Story = {};
