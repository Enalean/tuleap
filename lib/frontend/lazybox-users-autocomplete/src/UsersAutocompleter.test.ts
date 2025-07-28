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
import { okAsync, errAsync } from "neverthrow";
import type { GroupOfItems } from "@tuleap/lazybox";
import { Fault } from "@tuleap/fault";
import type { User } from "@tuleap/core-rest-api-types";
import { LazyboxStub } from "../tests/stubs/LazyboxStub";
import { UsersAutocompleter } from "./UsersAutocompleter";
import { GroupOfUsersBuilder } from "./GroupOfUsersBuilder";
import { UsersToLazyboxItemsTransformer } from "./UsersToLazyboxItemsTransformer";
import { GettextProviderStub } from "../tests/stubs/GettextProviderStub";

describe("UsersAutocompleter", () => {
    let lazybox: LazyboxStub;

    beforeEach(() => {
        lazybox = LazyboxStub.build();
    });

    const getNotNullGroupInDropdown = (): GroupOfItems => {
        const group = lazybox.getLastDropdownContent();
        if (group === null) {
            throw new Error("Expected to find a group inside lazybox's dropdown");
        }

        return group;
    };

    it(`Given that the user has typed less than 3 characters
        Then it should display an empty group with an empty message asking for more characters`, () => {
        const autocompleter = UsersAutocompleter(
            GroupOfUsersBuilder(UsersToLazyboxItemsTransformer(), GettextProviderStub),
            () => okAsync([]),
        );
        autocompleter.autocomplete(lazybox, [], "jo");

        const group = getNotNullGroupInDropdown();

        expect(group.items).toHaveLength(0);
        expect(group.empty_message).toBe("Type at least 3 characters");
    });

    it(`Given that the user has typed a user name
        Then it should push a loading group inside the dropdown while matching users are being fetched`, () => {
        const autocompleter = UsersAutocompleter(
            GroupOfUsersBuilder(UsersToLazyboxItemsTransformer(), GettextProviderStub),
            () => okAsync([]),
        );
        autocompleter.autocomplete(lazybox, [], "joe");

        const group = getNotNullGroupInDropdown();

        expect(group.is_loading).toBe(true);
    });

    it(`Given that matching users have been found
        Then it should display them in the dropdown`, async () => {
        const autocompleter = UsersAutocompleter(
            GroupOfUsersBuilder(UsersToLazyboxItemsTransformer(), GettextProviderStub),
            () =>
                okAsync([
                    {
                        id: 101,
                        display_name: "Joe l'Asticot",
                    } as User,
                    {
                        id: 102,
                        display_name: "Joe the Hobo",
                    } as User,
                ]),
        );

        await autocompleter.autocomplete(lazybox, [], "joe");

        const group = getNotNullGroupInDropdown();

        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(2);
    });

    it(`Given that no matching users have been found
        Then it should display an empty group in the dropdown`, async () => {
        const autocompleter = UsersAutocompleter(
            GroupOfUsersBuilder(UsersToLazyboxItemsTransformer(), GettextProviderStub),
            () => okAsync([]),
        );

        await autocompleter.autocomplete(lazybox, [], "nobody");

        const group = getNotNullGroupInDropdown();

        expect(group.empty_message).toBe("No matching users found");
        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(0);
    });

    it(`Given that an error occurred while retrieving matching users
        Then it should display an empty group`, async () => {
        const autocompleter = UsersAutocompleter(
            GroupOfUsersBuilder(UsersToLazyboxItemsTransformer(), GettextProviderStub),
            () => errAsync(Fault.fromMessage("An error that should probably never happen")),
        );

        await autocompleter.autocomplete(lazybox, [], "joe");

        const group = getNotNullGroupInDropdown();

        expect(group.empty_message).toBe("No matching users found");
        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(0);
    });
});
