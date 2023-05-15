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

import { createLazybox } from "@tuleap/lazybox";

export function initSingleLazybox() {
    const ADDITIONAL_ITEM_ID = 105;

    const item_105 = {
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
            value: { id: 103, color: "deep-blue", xref: "story #103", title: "And that too" },
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

    const items_group = {
        label: "✅ Matching items",
        empty_message: "No matching item",
        is_loading: false,
        items,
    };
    const recent_group = {
        label: "Recent items",
        empty_message: "No recent item",
        is_loading: false,
        items: recent_items,
    };
    const loading_group = {
        label: "Neverending loading",
        empty_message: "This group is always loading, it never ends.",
        is_loading: true,
        items: [],
    };

    const mount_point = document.getElementById("lazybox-link-selector");
    const lazybox = createLazybox(document);
    lazybox.options = {
        is_multiple: false,
        placeholder: "Please select an item to link",
        search_input_placeholder: "Type a number",
        new_item_button_label: "→ Create a new item…",
        new_item_callback: () => {
            const group_with_new_item = {
                ...items_group,
                items: [
                    ...items_group.items,
                    {
                        value: {
                            id: 108,
                            color: "firemist-silver",
                            xref: "story #108",
                            title: "New item",
                        },
                        is_disabled: false,
                    },
                ],
            };
            lazybox.replaceDropdownContent([group_with_new_item]);
        },
        templating_callback: (html, item) =>
            html`<span class="tlp-badge-${item.value.color} doc-link-selector-badge">
                    ${item.value.xref}
                </span>
                ${item.value.title}`,
        selection_callback: () => {
            // Do nothing
        },
        search_input_callback: (query) => {
            if (query === "") {
                lazybox.replaceDropdownContent([items_group, recent_group, loading_group]);
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
                    item.value.title.toLowerCase().includes(lowercase_query)
            );
            const matching_recent = recent_items.filter((item) =>
                item.value.title.toLowerCase().includes(lowercase_query)
            );
            const matching_items_group = { ...items_group, items: matching_items };
            const matching_recent_group = { ...recent_group, items: matching_recent };
            lazybox.replaceDropdownContent([matching_items_group, matching_recent_group]);
        },
    };
    lazybox.replaceDropdownContent([items_group, recent_group, loading_group]);
    mount_point.replaceWith(lazybox);
}

export function initMultipleLazybox() {
    const users = [
        {
            value: { id: 102, display_name: "Johnny Cash (jocash)" },
            is_disabled: false,
        },
        {
            value: { id: 102, display_name: "Joe l'Asticot (jolasti)" },
            is_disabled: false,
        },
        {
            value: { id: 103, display_name: "John doe (jdoe)" },
            is_disabled: false,
        },
        {
            value: { id: 104, display_name: "Joe the hobo (johobo)" },
            is_disabled: true,
        },
    ];
    const users_group = {
        label: "Matching users",
        empty_message: "No user found",
        is_loading: false,
        items: [],
    };
    const recent_users = [
        { value: { id: 105, display_name: "Jon Snow (jsnow)" }, is_disabled: false },
        { value: { id: 106, display_name: "Joe Dalton (jdalton)" }, is_disabled: false },
    ];
    const recent_group = {
        label: "Recent users",
        empty_message: "No user found",
        is_loading: false,
        items: [],
    };

    const mount_point = document.getElementById("lazybox-users-selector");
    const users_lazybox = createLazybox(document);
    users_lazybox.options = {
        is_multiple: true,
        placeholder: "Search users by names",
        templating_callback: (html, item) => html`
            <span class="doc-multiple-lazybox-user-with-avatar">
                <div class="tlp-avatar-mini"></div>
                ${item.value.display_name}
            </span>
        `,
        selection_callback: () => {
            // Do nothing
        },
        search_input_callback: (query) => {
            if (query === "") {
                users_lazybox.replaceDropdownContent([users_group]);
                return;
            }
            const lowercase_query = query.toLowerCase();
            const matching_users = users.filter((user) =>
                user.value.display_name.toLowerCase().includes(lowercase_query)
            );
            const matching_recent = recent_users.filter((user) =>
                user.value.display_name.toLowerCase().includes(lowercase_query)
            );
            const matching_users_group = { ...users_group, items: matching_users };
            const matching_recent_group = { ...recent_group, items: matching_recent };
            users_lazybox.replaceDropdownContent([matching_users_group, matching_recent_group]);
        },
    };
    users_lazybox.replaceDropdownContent([users_group]);
    users_lazybox.replaceSelection([users[0]]);
    mount_point.replaceWith(users_lazybox);
}
