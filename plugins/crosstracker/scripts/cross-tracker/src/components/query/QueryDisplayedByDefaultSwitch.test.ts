/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { WIDGET_ID } from "../../injection-symbols";
import QueryDisplayedByDefaultSwitch from "./QueryDisplayedByDefaultSwitch.vue";

describe("QueryDisplayedByDefaultSwitch", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof QueryDisplayedByDefaultSwitch>> {
        return shallowMount(QueryDisplayedByDefaultSwitch, {
            props: {
                is_default_query: false,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [WIDGET_ID.valueOf()]: 96,
                },
            },
        });
    }
    it("sends the update:is_default_query event when the switch is clicked", async () => {
        const wrapper = getWrapper();
        await wrapper.find("[data-test=query-checkbox]").trigger("input");
        expect(wrapper.emitted()).toHaveProperty("update:is_default_query");
        const event = wrapper.emitted("update:is_default_query");
        if (!event) {
            throw new Error("Expected a update:is_default_query event");
        }

        expect(event[0]).toStrictEqual([!wrapper.vm.is_default_query]);
    });
});
