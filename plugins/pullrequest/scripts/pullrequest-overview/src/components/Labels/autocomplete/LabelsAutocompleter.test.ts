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

import { describe, it, expect, beforeEach } from "vitest";
import type { GroupOfItems, LazyboxItem } from "@tuleap/lazybox";
import { LazyboxStub } from "../../../../tests/stubs/LazyboxStub";
import { LabelsAutocompleter } from "./LabelsAutocompleter";
import type { AutocompleteLabels } from "./LabelsAutocompleter";
import { GroupOfLabelsBuilder } from "./GroupOfLabelsBuilder";

const project_labels: LazyboxItem[] = [
    {
        value: {
            id: 101,
            label: "Easy fix",
            color: "peggy-pink",
            is_outline: true,
        },
        is_disabled: false,
    },
    {
        value: {
            id: 102,
            label: "Emergency",
            color: "fiesta-red",
            is_outline: false,
        },
        is_disabled: false,
    },
];

describe("LabelsAutocompleter", () => {
    let lazybox: LazyboxStub, autocompleter: AutocompleteLabels;

    beforeEach(() => {
        lazybox = LazyboxStub.build();
        autocompleter = LabelsAutocompleter(GroupOfLabelsBuilder((msgid: string): string => msgid));
    });

    const getNotNullGroupInDropdown = (): GroupOfItems => {
        const group = lazybox.getLastDropdownContent();
        if (group === null) {
            throw new Error("Expected to find a group inside lazybox's dropdown");
        }
        return group;
    };

    it(`Given that the query is empty
        Then it should display all the existing labels`, () => {
        autocompleter.autocomplete(lazybox, project_labels, [], "");

        const group = getNotNullGroupInDropdown();

        expect(group.items).toHaveLength(2);
    });

    it(`Given that matching labels have been found
        Then it should display them in the dropdown`, async () => {
        await autocompleter.autocomplete(lazybox, project_labels, [], "Emerg");

        const group = getNotNullGroupInDropdown();

        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(1);
    });

    it(`Given that no matching labels have been found
        Then it should display an empty group in the dropdown`, async () => {
        await autocompleter.autocomplete(lazybox, project_labels, [], "Nothing");

        const group = getNotNullGroupInDropdown();

        expect(group.empty_message).toBe("No matching labels found");
        expect(group.items).toHaveLength(0);
    });
});
