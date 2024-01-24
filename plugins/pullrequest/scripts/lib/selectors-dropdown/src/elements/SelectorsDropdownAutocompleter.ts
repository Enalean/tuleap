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
import type { SelectorEntry } from "./SelectorsDropdown";

export type Autocompleter = {
    start(selector: SelectorEntry, container: Element): Promise<void>;
};

const noop = (): void => {
    // Do nothing for the moment
};

export const SelectorsDropdownAutocompleter = (doc: Document): Autocompleter => ({
    start: async (selector: SelectorEntry, container: Element): Promise<void> => {
        const lazy_autocompleter = createLazyAutocompleter(doc);
        const { templating_callback, group, placeholder, loadItems } = selector.config;

        lazy_autocompleter.options = {
            placeholder,
            templating_callback,
            selection_callback: noop,
            search_input_callback: noop,
        };

        const selector_group = { ...group, is_loading: true };

        lazy_autocompleter.replaceContent([selector_group]);
        container.appendChild(lazy_autocompleter);

        const items = await loadItems();
        selector_group.is_loading = false;
        selector_group.items = items;
        lazy_autocompleter.replaceContent([selector_group]);
    },
});
