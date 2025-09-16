/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import WidgetWritingMode from "./WidgetWritingMode.vue";
import { Option } from "@tuleap/option";
import { usePersonalTimetrackingWidgetStore } from "../store/root";
import { PredefinedTimePeriodsVueStub } from "../../tests/stubs/PredefinedTimePeriodsVueStub";

vi.mock("@tuleap/tlp-date-picker", () => ({
    datePicker(): { setDate(): void } {
        return {
            setDate(): void {
                // Do nothing
            },
        };
    },
}));

describe("Given a personal timetracking widget in writing mode", () => {
    function getWritingModeInstance(): VueWrapper {
        return shallowMount(WidgetWritingMode, {
            global: {
                ...getGlobalTestOptions({
                    initial_state: {
                        root: {
                            selected_time_period: Option.nothing(),
                        },
                    },
                }),
                stubs: { "tuleap-predefined-time-period-select": PredefinedTimePeriodsVueStub },
            },
        });
    }

    it("When start date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWritingModeInstance();
        const predefined_time_period_stub = wrapper.findComponent(PredefinedTimePeriodsVueStub);

        const start_input = wrapper.find<HTMLInputElement>("[data-test=timetracking-start-date]");
        await start_input.setValue("2024-03-01");

        const store = usePersonalTimetrackingWidgetStore();

        expect(predefined_time_period_stub.vm.getCurrentlySelectedPredefinedTimePeriod()).toBe("");
        expect(store.selected_time_period.isNothing()).toBe(true);
    });

    it("When end date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWritingModeInstance();

        const predefined_time_period_stub = wrapper.findComponent(PredefinedTimePeriodsVueStub);

        const end_input = wrapper.find<HTMLInputElement>("[data-test=timetracking-end-date]");
        await end_input.setValue("2024-03-31");

        const store = usePersonalTimetrackingWidgetStore();

        expect(predefined_time_period_stub.vm.getCurrentlySelectedPredefinedTimePeriod()).toBe("");
        expect(store.selected_time_period.isNothing()).toBe(true);
    });
});
