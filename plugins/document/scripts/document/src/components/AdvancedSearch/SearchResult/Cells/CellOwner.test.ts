/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import CellOwner from "./CellOwner.vue";
import type { ItemSearchResult, User } from "../../../../type";
import UserBadge from "../../../User/UserBadge.vue";

describe("CellOwner", () => {
    it("should display the user badge", () => {
        const owner: User = {
            id: 102,
            uri: "users/102",
        } as unknown as User;

        const wrapper = shallowMount(CellOwner, {
            props: {
                item: {
                    owner,
                } as ItemSearchResult,
            },
        });

        const user_badge = wrapper.findComponent(UserBadge);
        expect(user_badge.exists()).toBe(true);
        expect(user_badge.props().user).toStrictEqual(owner);
    });
});
