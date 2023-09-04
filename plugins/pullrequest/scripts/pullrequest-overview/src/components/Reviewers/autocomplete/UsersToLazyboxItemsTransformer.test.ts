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
import { UsersToLazyboxItemsTransformer } from "./UsersToLazyboxItemsTransformer";

const user_1 = {
    id: 101,
    display_name: "Joe l'Asticot",
} as User;
const user_2 = {
    id: 102,
    display_name: "Joe the Hobo",
} as User;

describe("UsersToLazyboxItemsTransformer", () => {
    it(`buildForSelection() should build items to set lazybox's current selection`, () => {
        expect(UsersToLazyboxItemsTransformer().buildForSelection([user_1, user_2])).toStrictEqual([
            {
                id: String(user_1.id),
                value: user_1,
                is_disabled: false,
            },
            {
                id: String(user_2.id),
                value: user_2,
                is_disabled: false,
            },
        ]);
    });

    it(`buildForDropdown() should build items for lazybox's dropdown, disabling already selected users`, () => {
        expect(
            UsersToLazyboxItemsTransformer().buildForDropdown([user_1, user_2], [user_2]),
        ).toStrictEqual([
            {
                id: String(user_1.id),
                value: user_1,
                is_disabled: false,
            },
            {
                id: String(user_2.id),
                value: user_2,
                is_disabled: true,
            },
        ]);
    });
});
