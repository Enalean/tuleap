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
import FakeWorksheet from "./FakeWorksheet.vue";
import { getGlobalTestOptions } from "./global-options-for-test";
import FakeWorksheetColumn from "./FakeWorksheetColumn.vue";

describe("FakeWorksheet", () => {
    it("renders", () => {
        const wrapper = shallowMount(FakeWorksheet, {
            global: getGlobalTestOptions(),
            props: {
                tracker_name_level_1: "Tracker 1",
                tracker_name_level_2: null,
                tracker_name_level_3: null,
            },
        });

        expect(wrapper.findAllComponents(FakeWorksheetColumn)).toHaveLength(3);
        expect(wrapper.find(".fake-worksheet-disable-last-separator").exists()).toBe(true);
    });

    it("activates the last section separator when a level 2 tracker has been selected", () => {
        const wrapper = shallowMount(FakeWorksheet, {
            global: getGlobalTestOptions(),
            props: {
                tracker_name_level_1: "Tracker 1",
                tracker_name_level_2: "Tracker 2",
                tracker_name_level_3: null,
            },
        });

        expect(wrapper.find(".fake-worksheet-disable-last-separator").exists()).toBe(false);
    });
});
