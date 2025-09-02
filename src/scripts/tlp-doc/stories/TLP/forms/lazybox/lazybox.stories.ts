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
import type { Lazybox } from "@tuleap/lazybox";
import { buildLazyboxSingle } from "./lazybox-single";
import { buildLazyboxMultiple } from "./lazybox-multiple";
import "./lazybox.scss";

export type LazyboxProps = {
    story: "single" | "multiple";
    placeholder: string;
    search_input_placeholder: string;
    label_matching_items: string;
    label_recent_items: string;
    empty_message_matching_items: string;
    empty_message_recent_items: string;
    is_loading_matching_items: boolean;
    is_loading_recent_items: boolean;
    footer_message_matching_items: string;
    footer_message_recent_items: string;
};

function buildLazybox(args: LazyboxProps): Lazybox & HTMLElement {
    if (args.story === "single") {
        return buildLazyboxSingle(args);
    }
    return buildLazyboxMultiple(args);
}

function getTemplate(args: LazyboxProps): TemplateResult {
    const lazybox = buildLazybox(args);
    //prettier-ignore
    return html`
<div class="tlp-form-element">
    <label class="tlp-label" for="lazybox-${args.story}-link-selector">
        ${args.story} item picker
    </label>
</div>
${lazybox}`;
}

const meta: Meta<LazyboxProps> = {
    title: "TLP/Forms/Lazybox",
    parameters: {
        layout: "padded",
        controls: {
            exclude: ["story"],
        },
    },
    render: (args: LazyboxProps) => {
        return getTemplate(args);
    },
};

export default meta;
type Story = StoryObj<LazyboxProps>;

export const SingleItemPicker: Story = {
    args: {
        story: "single",
        placeholder: "Please select an item to link",
        search_input_placeholder: "Type a number",
        label_matching_items: "✅ Matching items",
        label_recent_items: "Recent items",
        empty_message_matching_items: "No matching item",
        empty_message_recent_items: "No recent item",
        is_loading_matching_items: false,
        is_loading_recent_items: false,
        footer_message_matching_items: "",
        footer_message_recent_items: "",
    },
    argTypes: {
        label_matching_items: {
            name: "label (Matching items)",
            description:
                "The label of the group. Think of it as the text content of an &lt;optgroup&gt;.",
        },
        label_recent_items: {
            name: "label (Recent items)",
        },
        empty_message_matching_items: {
            name: "empty_message (Matching items)",
            description:
                "The translated message that will be shown when there are no items. It is the 'empty state' of the group.",
        },
        empty_message_recent_items: {
            name: "empty_message (Recent items)",
        },
        is_loading_matching_items: {
            name: "is_loading (Matching items)",
            description: "Show a spinner next to the group's label if true.",
        },
        is_loading_recent_items: {
            name: "is_loading (Recent items)",
        },
        footer_message_matching_items: {
            name: "footer_message (Matching items)",
            description: "A message to display below the group of items.",
        },
        footer_message_recent_items: {
            name: "footer_message (Recent items)",
        },
    },
    parameters: {
        docs: {
            story: {
                height: "400px",
            },
        },
    },
};

export const MultipleItemsPicker: Story = {
    args: {
        story: "multiple",
        placeholder: "Search users by names",
    },
    parameters: {
        docs: {
            story: {
                height: "350px",
            },
        },
    },
};
