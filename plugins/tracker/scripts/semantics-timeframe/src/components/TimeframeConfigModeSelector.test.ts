/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import TimeframeConfigModeSelector from "./TimeframeConfigModeSelector.vue";
import { createSemanticTimeframeAdminLocalVue } from "../helpers/local-vue-for-tests";
import { MODE_BASED_ON_TRACKER_FIELDS, MODE_IMPLIED_FROM_ANOTHER_TRACKER } from "../constants";

describe("TimeframeConfigModeSelector", () => {
    async function getWrapper(is_implied: boolean): Promise<Wrapper<TimeframeConfigModeSelector>> {
        return shallowMount(TimeframeConfigModeSelector, {
            localVue: await createSemanticTimeframeAdminLocalVue(),
            propsData: {
                implied_from_tracker_id: is_implied ? 150 : "",
            },
        });
    }

    function getTimeframeModeSelectBox(
        wrapper: Wrapper<TimeframeConfigModeSelector>
    ): HTMLSelectElement {
        const select_box = wrapper.find("[data-test=timeframe-mode-select-box]").element;
        if (!(select_box instanceof HTMLSelectElement)) {
            throw new Error("<select> not found");
        }
        return select_box;
    }

    it("should display the inherited mode when there is an implied_from_tracker_id", async () => {
        const wrapper = await getWrapper(true);
        const select_box = getTimeframeModeSelectBox(wrapper);

        expect(select_box.value).toEqual(MODE_IMPLIED_FROM_ANOTHER_TRACKER);
    });

    it("should display the based on tracker fields mode", async () => {
        const wrapper = await getWrapper(false);
        const select_box = getTimeframeModeSelectBox(wrapper);

        expect(select_box.value).toEqual(MODE_BASED_ON_TRACKER_FIELDS);
    });

    it("should emit an event each time a new mode is selected", async () => {
        const wrapper = await getWrapper(false);
        const select_box = getTimeframeModeSelectBox(wrapper);

        jest.spyOn(wrapper.vm, "$emit");

        await wrapper.setData({ active_timeframe_mode: MODE_IMPLIED_FROM_ANOTHER_TRACKER });
        select_box.dispatchEvent(new Event("change"));

        expect(wrapper.vm.$emit).toHaveBeenCalledWith(
            "timeframe-mode-selected",
            MODE_IMPLIED_FROM_ANOTHER_TRACKER
        );

        await wrapper.setData({ active_timeframe_mode: MODE_BASED_ON_TRACKER_FIELDS });
        select_box.dispatchEvent(new Event("change"));

        expect(wrapper.vm.$emit).toHaveBeenCalledWith(
            "timeframe-mode-selected",
            MODE_BASED_ON_TRACKER_FIELDS
        );
    });
});
