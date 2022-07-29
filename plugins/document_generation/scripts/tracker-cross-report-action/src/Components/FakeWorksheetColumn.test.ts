/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import FakeWorksheetColumn from "./FakeWorksheetColumn.vue";
import { getGlobalTestOptions } from "./global-options-for-test";

describe("FakeWorksheetColumn", () => {
    it("disables columns when tracker name is not provided", () => {
        const wrapper = shallowMount(FakeWorksheetColumn, {
            global: getGlobalTestOptions(),
            props: {
                tracker_name: null,
            },
        });

        expect(wrapper.find(".worksheet-column-disabled").exists()).toBe(true);
    });

    it("does not disable column when a tracker name is provided", () => {
        const wrapper = shallowMount(FakeWorksheetColumn, {
            global: getGlobalTestOptions(),
            props: {
                tracker_name: "Some tracker name",
            },
        });

        expect(wrapper.find(".worksheet-column-disabled").exists()).toBe(false);
    });
});
