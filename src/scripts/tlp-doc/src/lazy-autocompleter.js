/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { createLazyAutocompleter } from "@tuleap/lazybox";

export function initLazyAutocompleter() {
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

    const items_group = {
        label: "✅ Matching items",
        empty_message: "No matching item",
        is_loading: false,
        items,
    };
    const mount_point = document.getElementById("lazy-autocompleter-links");
    const values_display = document.getElementById("lazy-autocompleter-links-value");
    const lazy_autocompleter = createLazyAutocompleter(document);
    lazy_autocompleter.options = {
        placeholder: "Type an id",
        templating_callback: (html, item) =>
            html`<span class="tlp-badge-${item.value.color} doc-link-selector-badge">
                    ${item.value.xref}
                </span>
                ${item.value.title}`,
        selection_callback: (selected_value) => {
            values_display.textContent = `${selected_value.xref} - ${selected_value.title}`;
        },
        search_input_callback: (query) => {
            if (query === "") {
                lazy_autocompleter.replaceContent([items_group]);
                return;
            }
            const lowercase_query = query.toLowerCase();

            if (lowercase_query === String(ADDITIONAL_ITEM_ID)) {
                lazy_autocompleter.replaceContent([{ ...items_group, items: [item_105] }]);
                return;
            }
            const matching_items = items.filter(
                (item) =>
                    String(item.value.id).includes(lowercase_query) ||
                    item.value.title.toLowerCase().includes(lowercase_query),
            );
            const matching_items_group = { ...items_group, items: matching_items };
            lazy_autocompleter.replaceContent([matching_items_group]);
        },
    };
    lazy_autocompleter.id = "lazy-autocompleter-links";
    lazy_autocompleter.replaceContent([items_group]);
    mount_point.replaceWith(lazy_autocompleter);
}
