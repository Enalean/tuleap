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

import { describe, it, expect, vi } from "vitest";
import * as lazy_autocompleter from "@tuleap/lazybox";
import type { LazyAutocompleter, LazyboxOptions } from "@tuleap/lazybox";
import { SelectorsDropdownAutocompleter } from "./SelectorsDropdownAutocompleter";

const noop = (): void => {
    // Do nothing
};

describe("SelectorsDropdownAutocompleter", () => {
    it(`start() should:
        - instanciate a lazy-autocompleter with the given selector configuration
        - mount the lazy-autocompleter
        - load the items
    `, () => {
        const doc = document.implementation.createHTMLDocument();
        const container = doc.createElement("div");
        const autocompleter = SelectorsDropdownAutocompleter(doc);
        const autocompleter_element = Object.assign(doc.createElement("div"), {
            replaceContent: noop,
            clearSelection: noop,
            replaceSelection: noop,
            options: {} as LazyboxOptions,
        }) as HTMLElement & LazyAutocompleter;

        const createLazyAutocompleter = vi
            .spyOn(lazy_autocompleter, "createLazyAutocompleter")
            .mockReturnValue(autocompleter_element);

        const config = {
            group: {
                items: [],
                is_loading: false,
                label: "matching authors",
                footer_message: "",
                empty_message: "Nothing to see here",
            },
            templating_callback: vi.fn(),
            placeholder: "Hold the place",
            loadItems: vi.fn().mockReturnValue([]),
        };

        autocompleter.start(
            {
                entry_name: "Author",
                config,
            },
            container,
        );

        expect(createLazyAutocompleter).toHaveBeenCalledWith(doc);
        expect(autocompleter_element.options.placeholder).toBe(config.placeholder);
        expect(autocompleter_element.options.templating_callback).toBe(config.templating_callback);
        expect(config.loadItems).toHaveBeenCalledOnce();
    });
});
