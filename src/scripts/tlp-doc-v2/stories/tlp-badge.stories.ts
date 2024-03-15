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
import { USER_INTERFACE_EMPHASIS_COLORS } from "@tuleap/core-constants";
import type { UserInterfaceEmphasisColorName } from "@tuleap/core-constants";
import { html } from "lit";
import "@tuleap/tlp-badge";
import { dark_background } from "../.storybook/backgrounds";
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

const meta: Meta<BadgeProps> = {
    title: "TLP/Badge",
    parameters: {
        controls: {
            exclude: ["all_variations", "on_dark_background"],
        },
    },
    render: (args: BadgeProps) =>
        args.all_variations
            ? html`<div class="doc-example-badges">
                  <span class="tlp-badge-primary ${getClasses(args)}">
                      <i class="fa-solid fa-tlp-tuleap tlp-badge-icon" aria-hidden="true"></i
                      >Badge</span
                  >
                  <span class="tlp-badge-primary ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-secondary ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-info ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-success ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-warning ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-danger ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-inca-silver ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-chrome-silver ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-firemist-silver ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-red-wine ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-fiesta-red ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-coral-pink ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-teddy-brown ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-clockwork-orange ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-graffiti-yellow ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-army-green ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-neon-green ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-acid-green ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-sherwood-green ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-ocean-turquoise ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-surf-green ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-deep-blue ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-lake-placid-blue ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-daphne-blue ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-plum-crazy ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-ultra-violet ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-lilac-purple ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-panther-pink ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-peggy-pink ${getClasses(args)}">Badge</span>
                  <span class="tlp-badge-flamingo-pink ${getClasses(args)}">Badge</span>
              </div>`
            : html`<span class="${getClasses(args)}"
                  >${args.with_icon
                      ? html`<i
                            class="fa-solid fa-tlp-tuleap tlp-badge-icon"
                            aria-hidden="true"
                        ></i>`
                      : ``}${args.label}</span
              >`,
    argTypes: {
        type: {
            name: "Type",
            description: "UI color of the badge",
            control: { type: "select" },
            options: USER_INTERFACE_EMPHASIS_COLORS,
            defaultValue: { summary: "primary" },
        },
        label: {
            name: "Label",
        },
        outline: {
            name: "Outline",
            description: "Applies the class",
            defaultValue: false,
            table: {
                type: { summary: "tlp-badge-outline" },
            },
            control: { type: "boolean" },
        },
        rounded: {
            name: "Rounded",
            description: `To be used only in some cases (as a counter in pane header, as a representation for numeric values only…)`,
            defaultValue: false,
            table: {
                type: { summary: "tlp-badge-rounded" },
            },
            control: { type: "boolean" },
        },
        with_icon: {
            name: "With icon",
            description: "Add an icon with the appropriate class",
            table: {
                type: { summary: "tlp-badge-icon" },
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
    parameters: {
        backgrounds: { default: dark_background.name, values: [dark_background] },
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
    parameters: {
        backgrounds: { default: dark_background.name, values: [dark_background] },
    },
    argTypes: {
        with_icon: { control: false },
    },
};
