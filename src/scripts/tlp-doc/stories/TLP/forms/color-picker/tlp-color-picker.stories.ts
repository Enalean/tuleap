/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { Meta, StoryObj } from "@storybook/web-components-vite";
import type { TemplateResult } from "lit";
import { html } from "lit";
import "./ColorPickerWrapper.ts";
import "./color-picker.scss";
import { COLOR_NAMES } from "@tuleap/core-constants";

type ColorPickerProps = {
    current_color: string;
    is_unsupported_color: boolean;
};

function getTemplate(args: ColorPickerProps): TemplateResult {
    return html` <div class="color-picker-wrapper">
        <label class="tlp-label" for="color_name">Choose your color ⇒</label>
        <tuleap-color-picker-wrapper
            .current_color="${args.current_color}"
            .is_unsupported_color="${args.is_unsupported_color}"
        ></tuleap-color-picker-wrapper>
    </div>`;
}

const meta: Meta<ColorPickerProps> = {
    title: "TLP/Forms/Color picker",
    parameters: {
        controls: {
            exclude: ["is_unsupported_color"],
        },
    },
    render: (args) => getTemplate(args),
    args: {
        current_color: "clockwork-orange",
        is_unsupported_color: false,
    },
    argTypes: {
        current_color: {
            description: "Color currently selected",
            control: "select",
            options: [...COLOR_NAMES, ""],
            table: {
                type: { summary: "string" },
            },
        },
        is_unsupported_color: {
            description: "Is the current color a legacy RGB color?",
            control: "boolean",
            table: {
                type: { summary: "boolean" },
            },
        },
    },
};
export default meta;

type Story = StoryObj<ColorPickerProps>;

export const ColorPicker: Story = {};

export const LegacyColor: Story = {
    args: {
        is_unsupported_color: true,
        current_color: "#586547",
    },
    argTypes: {
        current_color: {
            control: "color",
        },
    },
};
