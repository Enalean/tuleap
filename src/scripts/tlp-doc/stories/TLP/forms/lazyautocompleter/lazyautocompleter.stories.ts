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
import { html, type TemplateResult } from "lit";
import { buildLazyautocompleter } from "./lazyautocompleter";

export type LazyautocompleterProps = {
    placeholder: string;
    label: string;
    empty_message: string;
    is_loading: boolean;
    footer_message: string;
};

function getTemplate(args: LazyautocompleterProps): TemplateResult {
    const autocompleter = buildLazyautocompleter(args);
    //prettier-ignore
    return html`
<div>
    <p>Selected values: <span id="lazy-autocompleter-links-value"></span></p>
    <span id="lazy-autocompleter-links"></span>
</div>
${autocompleter}`
}

const meta: Meta<LazyautocompleterProps> = {
    title: "TLP/Forms/LazyAutocompleter",
    parameters: {
        layout: "padded",
        controls: {
            exclude: ["story"],
        },
    },
    render: (args: LazyautocompleterProps) => {
        return getTemplate(args);
    },
    args: {
        placeholder: "Type an id",
        label: "Recent items",
        empty_message: "No recent item",
        is_loading: false,
        footer_message: "",
    },
    argTypes: {
        empty_message: {
            description:
                "The translated message that will be shown when there are no items. It is the 'empty state' of the group.",
        },
        is_loading: {
            description: "Show a spinner next to the group's label if true.",
        },
        footer_message: {
            description: "A message to display below the group of items.",
        },
    },
};

export default meta;
type Story = StoryObj<LazyautocompleterProps>;

export const LazyAutocompleter: Story = {};
