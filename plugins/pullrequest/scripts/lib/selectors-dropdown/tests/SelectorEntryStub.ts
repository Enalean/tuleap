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

import { html } from "hybrids";
import type { SelectorEntry } from "../src/elements/SelectorsDropdown";
import type { LazyboxItem } from "@tuleap/lazybox";

export const SelectorEntryStub = {
    withEntryName: (entry_name: string): SelectorEntry => ({
        entry_name,
        config: {
            placeholder: "Hold the place",
            group: {
                label: "Test",
                empty_message: "Nothing to see here",
                is_loading: false,
                footer_message: "",
                items: [],
            },
            templating_callback: () => html``,
            loadItems(): Promise<[]> {
                return Promise.resolve([]);
            },
            filterItems(query: string, items: LazyboxItem[]): LazyboxItem[] {
                return items;
            },
        },
    }),
};
