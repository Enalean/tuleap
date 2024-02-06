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
import type { InternalSelectorsDropdown, SelectorEntry } from "./SelectorsDropdown";
import type { LazyboxItem } from "@tuleap/lazybox";
import { ContentGroupBuilder } from "./autocompleter/ContentGroupBuilder";
import { OnSelectionCallback } from "./autocompleter/OnSelectionCallback";

export type Autocompleter = {
    start(selector: SelectorEntry, host: InternalSelectorsDropdown): Promise<void>;
};

export const SelectorsDropdownAutocompleter = (doc: Document): Autocompleter => ({
    start: async (selector: SelectorEntry, host: InternalSelectorsDropdown): Promise<void> => {
        const group_builder = ContentGroupBuilder(selector.config);
        const lazy_autocompleter = createLazyAutocompleter(doc);

        const items: LazyboxItem[] = [];

        lazy_autocompleter.options = {
            placeholder: selector.config.placeholder,
            templating_callback: selector.config.templating_callback,
            selection_callback: OnSelectionCallback(
                host,
                lazy_autocompleter,
                group_builder,
                selector,
                items,
            ),
            search_input_callback: (query): void => {
                lazy_autocompleter.replaceContent([
                    group_builder.buildWithItems(selector.config.filterItems(query, items)),
                ]);
            },
        };

        host.auto_completer_element.replaceChildren(lazy_autocompleter);

        if (selector.isDisabled()) {
            lazy_autocompleter.disabled = selector.isDisabled();
            lazy_autocompleter.replaceContent([group_builder.buildEmptyAndDisabled()]);

            return;
        }

        lazy_autocompleter.replaceContent([group_builder.buildLoading()]);

        items.push(...(await selector.config.loadItems()));
        lazy_autocompleter.replaceContent([group_builder.buildWithItems(items)]);
    },
});
