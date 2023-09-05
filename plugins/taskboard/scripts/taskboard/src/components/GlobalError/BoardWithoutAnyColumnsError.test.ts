/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import BoardWithoutAnyColumnsError from "./BoardWithoutAnyColumnsError.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("BoardWithoutAnyColumnsError", () => {
    it("is displays misconfiguration error for regular user", () => {
        const wrapper = shallowMount(BoardWithoutAnyColumnsError, {
            mocks: { $store: createStoreMock({ state: { user: { user_is_admin: false } } }) },
        });
        expect(wrapper.element).toMatchInlineSnapshot(
            `<board-without-any-columns-error-for-users-stub />`,
        );
    });
    it("is displays misconfiguration error for admin user", () => {
        const wrapper = shallowMount(BoardWithoutAnyColumnsError, {
            mocks: { $store: createStoreMock({ state: { user: { user_is_admin: true } } }) },
        });
        expect(wrapper.element).toMatchInlineSnapshot(
            `<board-without-any-columns-error-for-admin-stub />`,
        );
    });
});
