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

import type { LazyAutocompleter, LazyAutocompleterSelectionCallback } from "@tuleap/lazybox";
import { Option } from "@tuleap/option";
import type { InternalSelectorsDropdown, SelectorEntry } from "../SelectorsDropdown";
import type { BuildContentGroup } from "./ContentGroupBuilder";

const forceHostToUpdateTemplate = (
    host: InternalSelectorsDropdown,
    selector: SelectorEntry,
): void => {
    host.active_selector = Option.fromValue(selector);
};

export const OnSelectionCallback =
    (
        host: InternalSelectorsDropdown,
        lazy_autocompleter: LazyAutocompleter,
        group_builder: BuildContentGroup,
        selector: SelectorEntry,
    ): LazyAutocompleterSelectionCallback =>
    (item_value: unknown): void => {
        selector.config.onItemSelection(item_value);
        if (!selector.isDisabled()) {
            return;
        }

        lazy_autocompleter.replaceContent([group_builder.buildEmptyAndDisabled()]);
        lazy_autocompleter.disabled = true;

        forceHostToUpdateTemplate(host, selector);
    };
