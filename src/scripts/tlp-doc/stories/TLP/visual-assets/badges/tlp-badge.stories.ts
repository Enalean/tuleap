/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Meta, StoryObj } from "@storybook/web-components";
import { USER_INTERFACE_EMPHASIS_COLORS, COLOR_VARIANTS } from "@tuleap/core-constants";
import type { UserInterfaceEmphasisColorName } from "@tuleap/core-constants";
import { html, type TemplateResult } from "lit";
import { dark_background } from "../../../../.storybook/backgrounds";
import "./tlp-badge.scss";

type BadgeProps = {
    type: UserInterfaceEmphasisColorName;
    label: string;
    outline: boolean;
    rounded: boolean;
    on_dark_background: boolean;
    with_icon: boolean;
    all_variations: boolean;
};

function getClasses(args: BadgeProps): string {
    const classes = [];
    if (!args.all_variations) {
        classes.push(`tlp-badge-${args.type}`);
    }
    if (args.outline) {
        classes.push("tlp-badge-outline");
    }
    if (args.rounded) {
        classes.push("tlp-badge-rounded");
    }
    if (args.on_dark_background) {
        classes.push("tlp-badge-on-dark-background");
    }
    return classes.join(" ");
}

function getTemplate(args: BadgeProps): TemplateResult {
    if (args.all_variations) {
        // prettier-ignore
        return html`
<div class="doc-example-badges">
    <span class="tlp-badge-primary ${getClasses(args)}">
        <i class="fa-solid fa-tlp-tuleap tlp-badge-icon" aria-hidden="true"></i> ${args.label}
    </span>${COLOR_VARIANTS.map((color) => html`
    <span class="tlp-badge-${color} ${getClasses(args)}">Badge</span>`)}
</div>`;
    }
    // prettier-ignore
    return html`
<span class="${getClasses(args)}">${args.with_icon ? html`<i class="fa-solid fa-tlp-tuleap tlp-badge-icon" aria-hidden="true"></i> ` : ``}${args.label}</span>`
}

const meta: Meta<BadgeProps> = {
    title: "TLP/Visual assets/Badges",
    parameters: {
        controls: {
            exclude: ["all_variations", "on_dark_background"],
        },
    },
    render: (args: BadgeProps) => {
        return getTemplate(args);
    },
    argTypes: {
        type: {
            name: "Type",
            description: "UI color of the badge",
            control: { type: "select" },
            options: USER_INTERFACE_EMPHASIS_COLORS,
        },
        label: {
            name: "Label",
        },
        outline: {
            name: "Outline",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-badge-outline" },
            },
            control: { type: "boolean" },
        },
        rounded: {
            name: "Rounded",
            description: `To be used only in some cases (as a counter in pane header, as a representation for numeric values only…)`,
            table: {
                type: { summary: ".tlp-badge-rounded" },
            },
            control: { type: "boolean" },
        },
        with_icon: {
            name: "With icon",
            description: "Add an icon with the appropriate class",
            table: {
                type: { summary: ".tlp-badge-icon" },
            },
            control: { type: "boolean" },
        },
    },
    args: {
        type: "primary",
        label: "Badge",
        outline: false,
        rounded: false,
        on_dark_background: false,
        with_icon: false,
    },
};

export default meta;
type Story = StoryObj<BadgeProps>;

export const Badge: Story = {
    args: {
        all_variations: false,
    },
};

export const BadgeOnDarkBackground: Story = {
    args: {
        outline: true,
        on_dark_background: true,
    },
    globals: {
        backgrounds: { value: dark_background.key },
    },
};

export const AllVariations: Story = {
    args: {
        all_variations: true,
    },
    argTypes: {
        with_icon: { control: false },
    },
};

export const AllVariationsOutline: Story = {
    args: {
        all_variations: true,
        outline: true,
    },
    argTypes: {
        with_icon: { control: false },
    },
};

export const AllVariationsRounded: Story = {
    args: {
        all_variations: true,
        rounded: true,
    },
    argTypes: {
        with_icon: { control: false },
    },
};

export const AllVariationsOnDarkBackground: Story = {
    args: {
        all_variations: true,
        outline: true,
        on_dark_background: true,
    },
    globals: {
        backgrounds: { value: dark_background.key },
    },
    argTypes: {
        with_icon: { control: false },
    },
};
