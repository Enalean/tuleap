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
import type {
    GroupCollection,
    GroupOfItems,
    LazyboxItem,
    LazyboxSelectionCallback,
} from "@tuleap/lazybox";

export type LazyboxStub = Lazybox & {
    getLastDropdownContent: () => GroupOfItems | null;
    getInitialSelection: () => ReadonlyArray<LazyboxItem>;
    selectItems: (items: unknown[]) => void;
};

const noop = (): void => {
    // Do nothing
};

export const LazyboxStub = {
    build: (selection_callback: LazyboxSelectionCallback = noop): LazyboxStub => {
        let last_group_of_items: GroupOfItems | null = null,
            initial_selection: readonly LazyboxItem[] = [];

        return {
            resetSelection: noop,
            setDropdownContent: (groups: GroupCollection): void => {
                last_group_of_items = groups[groups.length - 1];
            },
            setSelection: (items: readonly LazyboxItem[]): void => {
                initial_selection = items;

                selection_callback(initial_selection.map(({ value }) => value));
            },
            destroy: noop,
            getLastDropdownContent: () => last_group_of_items,
            getInitialSelection: () => initial_selection,
            selectItems: selection_callback,
        };
    },
};
