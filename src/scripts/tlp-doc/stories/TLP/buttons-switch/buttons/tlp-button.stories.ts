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
import { USER_INTERFACE_EMPHASIS_COLORS } from "@tuleap/core-constants";
import type { UserInterfaceEmphasisColorName } from "@tuleap/core-constants";
import "./tlp-button.scss";

type ButtonProps = {
    type: UserInterfaceEmphasisColorName;
    label: string;
    element: string;
    size: string;
    wide: boolean;
    outline: boolean;
    disabled: boolean;
    with_icon_on_left: boolean;
    with_icon_on_right: boolean;
    ellipsis_button: boolean;
};

const BUTTON_SIZES = ["default", "large", "small", "mini"];

function getClasses(args: ButtonProps): string {
    const classes = [`tlp-button-${args.type}`];
    if (args.size !== "default") {
        classes.push(`tlp-button-${args.size}`);
    }
    if (args.wide) {
        classes.push("tlp-button-wide");
    }
    if (args.outline) {
        classes.push("tlp-button-outline");
    }
    if (args.element === "button" && args.ellipsis_button) {
        classes.push("tlp-button-ellipsis");
    }
    if (args.element === "link" && args.disabled) {
        classes.push("disabled");
    }
    return classes.join(" ");
}

function getOptionalIcon(has_icon: boolean): TemplateResult {
    return has_icon
        ? html`<i class="fa-solid fa-comments tlp-button-icon" aria-hidden="true"></i> `
        : html``;
}

function getContent(args: ButtonProps): TemplateResult {
    if (args.ellipsis_button) {
        return html`<i class="fa-solid fa-ellipsis-h" aria-hidden="true"></i>`;
    }
    const left_icon = getOptionalIcon(args.with_icon_on_left);
    const right_icon = getOptionalIcon(args.with_icon_on_right);

    return html`${left_icon}${args.label}${right_icon}`;
}

function getButton(args: ButtonProps): TemplateResult {
    //prettier-ignore
    return html`
<button type="button" class="${getClasses(args)}" ?disabled=${args.disabled}>
    ${getContent(args)}
</button>`;
}

function getLink(args: ButtonProps): TemplateResult {
    //prettier-ignore
    return html`
<a class="${getClasses(args)}" role="button">
    ${getContent(args)}
</a>`;
}

function getInput(args: ButtonProps): TemplateResult {
    //prettier-ignore
    return html`<input type="button" class="${getClasses(args)}" value="Input" ?disabled=${args.disabled}>`;
}

const meta: Meta<ButtonProps> = {
    title: "TLP/Buttons & Switch/Buttons",
    parameters: {
        controls: {
            exclude: ["ellipsis_button"],
        },
    },
    render: (args) => {
        if (args.element === "link") {
            return getLink(args);
        }
        if (args.element === "input") {
            return getInput(args);
        }
        return getButton(args);
    },
    args: {
        type: "primary",
        label: "Button",
        element: "button",
        size: "default",
        wide: false,
        outline: false,
        disabled: false,
        with_icon_on_left: false,
        with_icon_on_right: false,
        ellipsis_button: false,
    },
    argTypes: {
        type: {
            name: "Type",
            description: "UI color of the button",
            control: "select",
            options: USER_INTERFACE_EMPHASIS_COLORS,
        },
        label: {
            name: "Label",
            if: { arg: "element", neq: "input" },
        },
        element: {
            name: "Element",
            description:
                "You would prefer &lt;button&gt;, but you can also use &lt;a&gt; or &lt;input&gt; elements.",
            control: "select",
            options: ["button", "link", "input"],
            table: {
                type: { summary: undefined },
            },
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
        wide: {
            name: "Wide",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-button-wide" },
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
            description: "Add disabled attribute. For &lt;a&gt; element, applies the class",
            table: {
                type: { summary: ".disabled" },
            },
        },
        with_icon_on_left: {
            name: "With icon on left",
            description: "Add an icon to the left, with the appropriate class",
            table: {
                type: { summary: ".tlp-button-icon" },
            },
            if: { arg: "element", neq: "input" },
        },
        with_icon_on_right: {
            name: "With icon on right",
            description: "Add an icon to the right, with the appropriate class",
            table: {
                type: { summary: ".tlp-button-icon" },
            },
            if: { arg: "element", neq: "input" },
        },
    },
    decorators: [(story): TemplateResult => html`<div class="button-wrapper">${story()}</div>`],
};

export default meta;
type Story = StoryObj<ButtonProps>;

export const Button: Story = {};

export const EllipsisButton: Story = {
    args: {
        ellipsis_button: true,
    },
    argTypes: {
        label: {
            name: "Label",
            control: false,
        },
        element: {
            name: "Element",
            control: false,
        },
        wide: {
            name: "Wide",
            control: false,
        },
        outline: {
            name: "Outline",
            control: false,
        },
        with_icon_on_left: {
            name: "With icon on left",
            control: false,
        },
        with_icon_on_right: {
            name: "With icon on right",
            control: false,
        },
    },
};
