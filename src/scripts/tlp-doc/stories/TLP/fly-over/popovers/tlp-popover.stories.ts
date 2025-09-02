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
import { USER_INTERFACE_COLORS, type UserInterfaceColorName } from "@tuleap/core-constants";
import { html } from "lit";
import "./PopoverWrapper";
import "./popover.scss";

type PositionRelativeToAnchor = "top" | "bottom" | "left" | "right";
type PositionOfArrow = "start" | "end";
type PopoverPlacement = PositionRelativeToAnchor | `${PositionRelativeToAnchor}-${PositionOfArrow}`;

type PopoverProps = {
    placement: PopoverPlacement;
    trigger: "hover" | "click";
    dark: boolean;
    user_interface_color: "" | UserInterfaceColorName;
    with_footer: boolean;
    with_anchor: boolean;
};

const PLACEMENTS = [
    "top-start",
    "top",
    "top-end",
    "bottom-start",
    "bottom",
    "bottom-end",
    "right-start",
    "right",
    "right-end",
    "left-start",
    "left",
    "left-end",
];

function getClasses(args: PopoverProps): string {
    const classes = [`tlp-popover`];
    if (args.user_interface_color !== "") {
        classes.push(`tlp-popover-${args.user_interface_color}`);
    }
    if (args.dark) {
        classes.push(`tlp-popover-dark`);
    }
    return classes.join(" ");
}

function getTemplate(args: PopoverProps): TemplateResult {
    // prettier-ignore
    return html`${args.with_anchor ? html`
<p>Use <code id="popover-anchor-example" data-placement=${args.placement}>anchor</code> option to stick the popover to an
element and trigger it <a href="https://example.com" id="popover-anchor-example-trigger">from somewhere else</a>
in the page.
</p>`: html`
<p>Like tooltips, popovers can display <br>
    meaningful
    <a
       class="popover-example"
       id="popover-example"
       data-placement=${args.placement}
       data-trigger=${args.trigger}
    >information</a>.
</p>`}

<section class=${getClasses(args)}  id="popover-example-content">
    <div class="tlp-popover-arrow"></div>
    <div class="tlp-popover-header">
        <h1 class="tlp-popover-title">For your information...</h1>
    </div>
    <div class="tlp-popover-body">
        <p>
            This is an example of sentence that you can find on a popover.
        </p>
    </div>${args.with_footer ? html`
    <div class="tlp-popover-footer">
        <button type="button" class="tlp-button-primary tlp-button-small tlp-button-outline" data-dismiss="popover">Cancel</button>
        <button type="button" class="tlp-button-primary tlp-button-small">Action</button>
    </div>` : ``}
</section>`;
}

const meta: Meta<PopoverProps> = {
    title: "TLP/Fly Over/Popovers",
    parameters: {
        controls: {
            exclude: ["with_anchor"],
        },
    },
    render: (args: PopoverProps) => {
        return getTemplate(args);
    },
    args: {
        placement: "bottom",
        user_interface_color: "",
        trigger: "hover",
        dark: false,
        with_footer: false,
        with_anchor: false,
    },
    argTypes: {
        placement: {
            description: "Defaults to 'bottom'. Overrides corresponding data-placement attribute",
            control: "select",
            options: PLACEMENTS,
        },
        user_interface_color: {
            name: "UI color",
            description: "UI color of the popover",
            control: "select",
            options: [...USER_INTERFACE_COLORS, ""],
            table: {
                type: { summary: undefined },
            },
        },
        trigger: {
            description:
                "Defaults to 'hover'. If 'click', popover is displayed on click instead of mouseover. Overrides corresponding data-trigger attribute",
            control: "select",
            options: ["hover", "click"],
        },
        dark: {
            description:
                "By default, popovers have a white background, to set a dark background apply the class",
            table: {
                type: { summary: ".tlp-popover-dark" },
            },
        },
        with_footer: {
            name: "With footer",
            description:
                "Popovers have a header, a body and optionally a footer. Footer is useful for user interaction like confirmation or cancel buttons",
            table: {
                type: { summary: undefined },
            },
        },
    },
    decorators: [
        (story, { args }: { args: PopoverProps }): TemplateResult =>
            html`<div class="popover-wrapper">
                <tuleap-popover-wrapper
                    .trigger=${args.trigger}
                    .placement=${args.placement}
                    .anchor=${args.with_anchor}
                    >${story()}</tuleap-popover-wrapper
                >
            </div>`,
    ],
};

export default meta;
type Story = StoryObj<PopoverProps>;

export const Popover: Story = {};

export const PopoverWithAnchor: Story = {
    args: {
        with_anchor: true,
    },
};
