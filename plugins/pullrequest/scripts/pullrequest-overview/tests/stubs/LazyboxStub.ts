/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { Lazybox } from "@tuleap/lazybox";
import type { GroupOfItems, LazyboxItem } from "@tuleap/lazybox";

export type LazyboxStub = Lazybox & {
    getLastDropdownContent(): GroupOfItems | null;
    getInitialSelection(): ReadonlyArray<LazyboxItem>;
    selectItems(items: unknown[]): void;
    createItem(item_name: string): void;
};

const noop = (): void => {
    // Do nothing
};

export const LazyboxStub = {
    build: (): LazyboxStub => {
        let last_group_of_items: GroupOfItems | null = null,
            initial_selection: readonly LazyboxItem[] = [];

        return {
            options: {
                is_multiple: true,
                placeholder: "",
                templating_callback: (html) => html``,
                selection_callback: (items): void => {
                    if (items) {
                        //Do nothing
                    }
                },
                search_input_callback: (query): void => {
                    if (query) {
                        //Do nothing
                    }
                },
            },
            clearSelection: noop,
            replaceDropdownContent: (groups): void => {
                last_group_of_items = groups[groups.length - 1];
            },
            replaceSelection(items): void {
                initial_selection = items;
                this.options.selection_callback(initial_selection.map(({ value }) => value));
            },
            getLastDropdownContent: () => last_group_of_items,
            getInitialSelection: () => initial_selection,
            selectItems(items): void {
                this.options.selection_callback(items);
            },
            createItem(item_name: string): void {
                if (!this.options.new_item_callback) {
                    throw new Error(
                        "Expected to have new_item_callback defined. Please check it is defined in your lazybox options"
                    );
                }

                this.options.new_item_callback(item_name);
            },
        };
    },
};
