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

import { describe, it, expect } from "vitest";
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { BuildGroupOfReviewers } from "./GroupOfReviewersBuilder";
import { GroupOfReviewersBuilder } from "./GroupOfReviewersBuilder";
import { UsersToLazyboxItemsTransformer } from "./UsersToLazyboxItemsTransformer";

const getGroupBuilder = (): BuildGroupOfReviewers =>
    GroupOfReviewersBuilder(UsersToLazyboxItemsTransformer(), (msgid: string): string => msgid);

describe("GroupOfReviewersBuilder", () => {
    it("buildEmpty() should build an empty group", () => {
        const group = getGroupBuilder().buildEmpty();

        expect(group.empty_message).toBe("No matching users found");
        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(0);
    });

    it('buildEmptyNotEnoughCharacters() should build an empty group with "Type at least 3 characters" as empty message', () => {
        const group = getGroupBuilder().buildEmptyNotEnoughCharacters();

        expect(group.empty_message).toBe("Type at least 3 characters");
        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(0);
    });

    it("buildLoading() should build an empty loading group", () => {
        const group = getGroupBuilder().buildLoading();

        expect(group.empty_message).toBe("");
        expect(group.is_loading).toBe(true);
        expect(group.items).toHaveLength(0);
    });

    it("buildWithUsers() should build a group containing the provided users", () => {
        const group = getGroupBuilder().buildWithUsers(
            [
                {
                    id: 101,
                    display_name: "Joe l'Asticot",
                } as User,
                {
                    id: 102,
                    display_name: "Joe the Hobo",
                } as User,
            ],
            [],
        );

        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(2);
    });
});
