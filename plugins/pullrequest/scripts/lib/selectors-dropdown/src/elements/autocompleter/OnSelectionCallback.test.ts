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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { Option } from "@tuleap/option";
import type { LazyAutocompleter, GroupCollection, LazyboxSelectionCallback } from "@tuleap/lazybox";
import { SelectorEntryStub } from "../../../tests/SelectorEntryStub";
import type { InternalSelectorsDropdown, SelectorEntry } from "../SelectorsDropdown";
import { OnSelectionCallback } from "./OnSelectionCallback";
import { ContentGroupBuilder } from "./ContentGroupBuilder";

describe("OnSelectionCallback", () => {
    let replaced_groups: GroupCollection = [],
        selector: SelectorEntry,
        lazy_autocompleter: LazyAutocompleter,
        is_disabled_after_selection: boolean;

    beforeEach(() => {
        is_disabled_after_selection = false;
    });

    const getCallback = (): LazyboxSelectionCallback => {
        selector = SelectorEntryStub.withEntryName("test");
        lazy_autocompleter = {
            replaceContent: (groups) => {
                replaced_groups = groups;
            },
            disabled: false,
        } as LazyAutocompleter;

        vi.spyOn(selector, "isDisabled").mockReturnValue(is_disabled_after_selection);
        vi.spyOn(selector.config, "onItemSelection");

        return OnSelectionCallback(
            {
                active_selector: Option.fromValue(selector),
            } as InternalSelectorsDropdown,
            lazy_autocompleter,
            ContentGroupBuilder(selector.config),
            selector,
        );
    };

    it("When the selector is disabled after the selection occurred, then the autocompleter list should be emptied and disabled", () => {
        is_disabled_after_selection = true;

        const callback = getCallback();
        const selected_item = "value 1";

        callback(selected_item);

        expect(selector.config.onItemSelection).toHaveBeenCalledOnce();
        expect(selector.config.onItemSelection).toHaveBeenCalledWith(selected_item);
        expect(replaced_groups[0].empty_message).toStrictEqual(selector.config.disabled_message);
        expect(lazy_autocompleter.disabled).toBe(true);
    });

    it("When the selector is NOT disabled after the selection occurred, then the autocompleter should not be disabled", () => {
        is_disabled_after_selection = false;

        const callback = getCallback();
        const selected_item = "value 1";

        callback(selected_item);

        expect(selector.config.onItemSelection).toHaveBeenCalledOnce();
        expect(selector.config.onItemSelection).toHaveBeenCalledWith(selected_item);
        expect(lazy_autocompleter.disabled).toBe(false);
    });
});
