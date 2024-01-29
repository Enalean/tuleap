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

import type { GroupOfItems } from "@tuleap/lazybox";
import type { AutocompleterConfig } from "../SelectorsDropdown";
import type { LazyboxItem } from "@tuleap/lazybox/src/GroupCollection";

export type BuildContentGroup = {
    buildLoading(): GroupOfItems;
    buildWithItems(items: LazyboxItem[]): GroupOfItems;
    buildEmptyAndDisabled(): GroupOfItems;
};

const getBaseGroupFromConfig = (config: AutocompleterConfig): GroupOfItems => ({
    items: [],
    empty_message: config.empty_message,
    label: config.label,
    footer_message: "",
    is_loading: false,
});

export const ContentGroupBuilder = (config: AutocompleterConfig): BuildContentGroup => ({
    buildLoading: (): GroupOfItems => ({
        ...getBaseGroupFromConfig(config),
        is_loading: true,
    }),
    buildWithItems: (items: LazyboxItem[]) => ({
        ...getBaseGroupFromConfig(config),
        items,
    }),
    buildEmptyAndDisabled: () => ({
        ...getBaseGroupFromConfig(config),
        empty_message: config.disabled_message,
    }),
});
