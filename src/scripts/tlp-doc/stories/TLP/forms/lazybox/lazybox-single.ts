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

import type { GroupOfItems, HTMLTemplateResult, Lazybox, LazyboxItem } from "@tuleap/lazybox";
import { createLazybox } from "@tuleap/lazybox";
import type { LazyboxProps } from "./lazybox.stories";
import type { ColorName } from "@tuleap/core-constants";

type Artifact = {
    readonly id: number;
    readonly color: ColorName;
    readonly xref: string;
    readonly title: string;
};

function isArtifact(item: unknown): item is Artifact {
    return typeof item === "object" && item !== null && "id" in item;
}

const ADDITIONAL_ITEM_ID = 105;

const item_105: LazyboxItem = {
    value: {
        id: ADDITIONAL_ITEM_ID,
        color: "graffiti-yellow",
        xref: "story #105",
        title: "Do more stuff",
    },
    is_disabled: false,
};

const items = [
    {
        value: { id: 101, color: "acid-green", xref: "story #101", title: "Do this" },
        is_disabled: false,
    },
    {
        value: { id: 102, color: "fiesta-red", xref: "story #102", title: "Do that" },
        is_disabled: false,
    },
    {
        value: {
            id: 103,
            color: "deep-blue",
            xref: "story #103",
            title: "And that too",
        },
        is_disabled: true,
    },
];

const recent_items = [
    {
        value: {
            id: 106,
            color: "lake-placid-blue",
            xref: "request #106",
            title: "Please fix",
        },
        is_disabled: false,
    },
    {
        value: {
            id: 107,
            color: "ocean-turquoise",
            xref: "request #107",
            title: "It does not work",
        },
        is_disabled: false,
    },
];

export function buildLazyboxSingle(args: LazyboxProps): Lazybox & HTMLElement {
    const items_group: GroupOfItems = {
        label: args.label_matching_items,
        empty_message: args.empty_message_matching_items,
        is_loading: args.is_loading_matching_items,
        items,
        footer_message: args.footer_message_matching_items,
    };
    const recent_group: GroupOfItems = {
        label: args.label_recent_items,
        empty_message: args.empty_message_recent_items,
        is_loading: args.is_loading_recent_items,
        items: recent_items,
        footer_message: args.footer_message_recent_items,
    };
    const lazybox = createLazybox(document);
    lazybox.id = `lazybox-${args.story}-link-selector`;
    lazybox.options = {
        is_multiple: false,
        placeholder: args.placeholder,
        search_input_placeholder: args.search_input_placeholder,
        new_item_label_callback: (item_name): string =>
            item_name !== "" ? `→ Create a new item "${item_name}"…` : "→ Create a new item…",
        new_item_clicked_callback: (item_name): void => {
            lazybox.replaceSelection([
                {
                    value: {
                        id: 108,
                        color: "firemist-silver",
                        xref: "story #108",
                        title: item_name !== "" ? item_name : "New item",
                    },
                    is_disabled: false,
                },
            ]);
        },
        templating_callback: (html, item): HTMLTemplateResult => {
            if (!isArtifact(item.value)) {
                return html``;
            }
            return html`<span class="tlp-badge-${item.value.color} doc-lazybox-badge"
                    >${item.value.xref}</span
                >
                ${item.value.title}`;
        },
        selection_callback: (): void => {
            // Do nothing
        },
        search_input_callback: (query): void => {
            if (query === "") {
                lazybox.replaceDropdownContent([items_group, recent_group]);
                return;
            }
            const lowercase_query = query.toLowerCase();

            if (lowercase_query === String(ADDITIONAL_ITEM_ID)) {
                lazybox.replaceDropdownContent([{ ...items_group, items: [item_105] }]);
                return;
            }
            const matching_items = items.filter(
                (item) =>
                    String(item.value.id).includes(lowercase_query) ||
                    item.value.title.toLowerCase().includes(lowercase_query),
            );
            const matching_recent = recent_items.filter((item) =>
                item.value.title.toLowerCase().includes(lowercase_query),
            );
            const matching_items_group = { ...items_group, items: matching_items };
            const matching_recent_group = { ...recent_group, items: matching_recent };
            lazybox.replaceDropdownContent([matching_items_group, matching_recent_group]);
        },
    };
    lazybox.replaceDropdownContent([items_group, recent_group]);
    return lazybox;
}
