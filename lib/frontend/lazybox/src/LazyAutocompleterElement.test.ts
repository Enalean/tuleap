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
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { HostElement } from "./LazyAutocompleterElement";
import {
    buildObserveDisabled,
    buildReplaceContent,
    getSearchInput,
} from "./LazyAutocompleterElement";
import type { GroupCollection, LazyboxItem } from "./GroupCollection";
import { GroupCollectionBuilder } from "../tests/builders/GroupCollectionBuilder";
import { OptionsAutocompleterBuilder } from "../tests/builders/OptionsAutocompleterBuilder";
import type { LazyAutocompleterOptions } from "./Options";

const noopSelectItem = (item: LazyboxItem): void => {
    if (item) {
        //Do nothing
    }
};

describe("LazyAutocompleterElement", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe(`methods`, () => {
        const getHost = (): HostElement => {
            const groups: GroupCollection = [];
            return {
                dropdown_element: { groups },
                selection: {
                    selectItem: noopSelectItem,
                    isSelected: (item) => (item ? false : false),
                },
            } as HostElement;
        };

        it(`replaceDropdownContent() replaces the dropdown content with the new one`, () => {
            const host = getHost();
            buildReplaceContent(host)(GroupCollectionBuilder.withSingleGroup({}));
            expect(host.dropdown_element.groups).toHaveLength(1);
        });
    });

    describe(`Search Input`, () => {
        let options: LazyAutocompleterOptions;
        beforeEach(() => {
            options = OptionsAutocompleterBuilder.someOptions().build();
        });

        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                options,
                dropdown_element: { open: false },
            }) as HostElement;

        it(`makes the element focusable`, () => {
            const search_input = getSearchInput(getHost());
            expect(search_input.getAttribute("tabindex")).toBe("0");
        });

        it(`when it receives "search-input" event,
            it will call the search_input_callback with the search input's text`, () => {
            const search_input_callback = vi.spyOn(options, "search_input_callback");
            const host = getHost();
            const search_input = getSearchInput(host);
            const query = "stepfatherly";
            search_input.getQuery = (): string => query;

            search_input.dispatchEvent(new CustomEvent("search-input"));

            expect(search_input_callback).toHaveBeenCalledWith(query);
        });

        it(`assigns the placeholder from options`, () => {
            const PLACEHOLDER = "I hold the place";
            options = OptionsAutocompleterBuilder.someOptions()
                .withPlaceholder(PLACEHOLDER)
                .build();
            const search_input = getSearchInput(getHost());

            expect(search_input.placeholder).toBe(PLACEHOLDER);
        });
    });

    describe("disabled attribute", () => {
        const getHost = (): HostElement =>
            Object.assign(doc.createElement("span"), {
                options: OptionsAutocompleterBuilder.someOptions().build(),
                search_input_element: {
                    disabled: false,
                    clear: () => {
                        // do nothing
                    },
                },
            }) as HostElement;

        it("When the disabled attribute changes from false to true, then it should update the search-input disabled attribute and clear its query", () => {
            const host = getHost();

            vi.spyOn(host.search_input_element, "clear");

            buildObserveDisabled(host, true, false);

            expect(host.search_input_element.disabled).toBe(true);
            expect(host.search_input_element.clear).toHaveBeenCalledOnce();
        });

        it("When the disabled attribute changes from true to false, then it should not clear its query", () => {
            const host = getHost();

            vi.spyOn(host.search_input_element, "clear");

            buildObserveDisabled(host, false, true);

            expect(host.search_input_element.disabled).toBe(false);
            expect(host.search_input_element.clear).not.toHaveBeenCalledOnce();
        });
    });
});
