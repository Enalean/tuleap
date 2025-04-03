/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ReadingMode from "./ReadingMode.vue";
import type { Query } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { IS_USER_ADMIN, WIDGET_ID } from "../../injection-symbols";

describe("ReadingMode", () => {
    let reading_query: Query, is_user_admin: boolean, has_error: boolean;

    beforeEach(() => {
        reading_query = {
            id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            tql_query: "",
            title: "",
            description: "a great reading query",
            is_default: false,
        };
        is_user_admin = true;
        has_error = false;
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof ReadingMode>> {
        return shallowMount(ReadingMode, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [WIDGET_ID.valueOf()]: 875,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                },
            },
            props: {
                has_error,
                reading_query,
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it("When I switch to the writing mode, then an event will be emitted", () => {
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeDefined();
        });

        it(`Given I am browsing as project member,
            when I try to switch to writing mode, nothing will happen`, () => {
            is_user_admin = false;
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeUndefined();
        });
    });
});
