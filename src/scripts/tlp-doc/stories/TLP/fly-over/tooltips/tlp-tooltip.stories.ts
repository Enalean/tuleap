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

type TooltipProps = {
    placement: "top" | "right" | "bottom" | "left";
    tag: "span" | "link" | "badge" | "button";
};

function getTemplate(args: TooltipProps): TemplateResult {
    switch (args.tag) {
        default:
            return html``;
        case "span":
            // prettier-ignore
            return html`
<span class="tlp-tooltip tlp-tooltip-${args.placement}" data-tlp-tooltip="Hey look, I'm a tooltip!">
    Tooltip on ${args.placement}
</span>`;
        case "link":
            // prettier-ignore
            return html`
<a href="https://example.com" class="tlp-tooltip tlp-tooltip-${args.placement}" data-tlp-tooltip="Hey look, I'm a tooltip!">
    Tooltip on ${args.placement}
</a>`;
        case "badge":
            // prettier-ignore
            return html`
<span class="tlp-badge-success tlp-tooltip tlp-tooltip-${args.placement}" data-tlp-tooltip="Hey look, I'm a tooltip!">
    Tooltip on ${args.placement}
</span>`;
        case "button":
            // prettier-ignore
            return html`
<button type="button" class="tlp-button-primary tlp-tooltip tlp-tooltip-${args.placement}" data-tlp-tooltip="Hey look, I'm a tooltip!">
    Tooltip on ${args.placement}
</button>`;
    }
}

const meta: Meta<TooltipProps> = {
    title: "TLP/Fly Over/Tooltips",
    render: (args: TooltipProps) => {
        return getTemplate(args);
    },
    args: {
        placement: "right",
        tag: "span",
    },
    argTypes: {
        placement: {
            name: "Placement",
            description: "Tooltip placement",
            control: "select",
            options: ["top", "right", "bottom", "left"],
            table: {
                type: { summary: undefined },
            },
        },
        tag: {
            name: "TAG HTML",
            control: "select",
            options: ["span", "link", "badge", "button"],
            table: {
                type: { summary: undefined },
            },
        },
    },
};

export default meta;
type Story = StoryObj<TooltipProps>;

export const Tooltip: Story = {};
