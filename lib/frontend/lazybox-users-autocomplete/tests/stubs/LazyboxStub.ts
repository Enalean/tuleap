/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { Lazybox, GroupOfItems } from "@tuleap/lazybox";

export type LazyboxStub = Lazybox & {
    getLastDropdownContent(): GroupOfItems | null;
};

const noop = (): void => {
    // Do nothing
};

export const LazyboxStub = {
    build: (): LazyboxStub => {
        let last_group_of_items: GroupOfItems | null = null;

        return {
            options: {
                is_multiple: true,
                placeholder: "",
                templating_callback: (html) => html``,
                selection_callback: noop,
                search_input_callback: noop,
            },
            clearSelection: noop,
            replaceDropdownContent: (groups): void => {
                last_group_of_items = groups[groups.length - 1];
            },
            replaceSelection: noop,
            getLastDropdownContent: () => last_group_of_items,
        };
    },
};
