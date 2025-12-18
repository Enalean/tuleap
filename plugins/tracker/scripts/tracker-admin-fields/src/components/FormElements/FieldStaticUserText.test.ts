/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldStaticUserText from "./FieldStaticUserText.vue";
import { LAST_UPDATED_BY_FIELD } from "@tuleap/plugin-tracker-constants";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { CURRENT_USER, IS_USER_LOADING } from "../../injection-symbols";
import { Option } from "@tuleap/option";
import type { User } from "@tuleap/core-rest-api-types";

describe("FieldStaticUserText", () => {
    const getWrapper = (is_user_loading: boolean): VueWrapper =>
        shallowMount(FieldStaticUserText, {
            props: {
                field: {
                    field_id: 123,
                    name: "details",
                    label: "Details",
                    type: LAST_UPDATED_BY_FIELD,
                    required: false,
                },
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [CURRENT_USER.valueOf()]: Option.nothing<User>(),
                    [IS_USER_LOADING.valueOf()]: is_user_loading,
                },
            },
        });
    it("displays a skeleton when the user avatar and display name is loading", () => {
        const wrapper = getWrapper(true);
        expect(wrapper.find("[data-test=field-static-user-text-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=field-static-user-text]").exists()).toBe(false);
    });

    it("the user avatar and display name", () => {
        const wrapper = getWrapper(false);
        expect(wrapper.find("[data-test=field-static-user-text-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=field-static-user-text]").exists()).toBe(true);
    });
});
