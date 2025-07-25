/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import type {
    ReadonlyFieldUser,
    ReadonlyFieldUserList,
} from "@/sections/readonly-fields/ReadonlyFields";
import FieldUserList from "@/components/section/readonly-fields/FieldUserList.vue";
import { DISPLAY_TYPE_COLUMN } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";

describe("FieldUserList", () => {
    const getWrapper = (user_list_field: ReadonlyFieldUserList | ReadonlyFieldUser): VueWrapper =>
        shallowMount(FieldUserList, {
            props: {
                user_list_field,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

    it("When the field has no values, then it should display an empty state", () => {
        const wrapper = getWrapper(ReadonlyFieldStub.userList([], DISPLAY_TYPE_COLUMN));

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.findAll("[data-test=user-list-item]")).toHaveLength(0);
    });

    it("Given a user list field, then it should display the users with their avatars", () => {
        const user_bob = { display_name: "Bob", avatar_url: "https://example.com/bob_avatar.png" };
        const user_alice = {
            display_name: "Alice",
            avatar_url: "https://example.com/alice_avatar.png",
        };
        const wrapper = getWrapper(
            ReadonlyFieldStub.userList([user_bob, user_alice], DISPLAY_TYPE_COLUMN),
        );

        expect(wrapper.findAll("[data-test=user-list-item]")).toHaveLength(2);

        const [bob, alice] = wrapper.findAll("[data-test=user-list-item]");

        expect(bob.text()).toBe(user_bob.display_name);
        expect(bob.find("[data-test=user-list-item-avatar]").attributes("src")).toBe(
            user_bob.avatar_url,
        );

        expect(alice.text()).toBe(user_alice.display_name);
        expect(alice.find("[data-test=user-list-item-avatar]").attributes("src")).toBe(
            user_alice.avatar_url,
        );
    });

    it("Given a user field, then it should display it", () => {
        const user_bob = { display_name: "Bob", avatar_url: "https://example.com/bob_avatar.png" };
        const wrapper = getWrapper(ReadonlyFieldStub.userField(user_bob, DISPLAY_TYPE_COLUMN));

        const bob = wrapper.find("[data-test=user-list-item]");

        expect(bob.text()).toBe(user_bob.display_name);
        expect(bob.find("[data-test=user-list-item-avatar]").attributes("src")).toBe(
            user_bob.avatar_url,
        );
    });
});
