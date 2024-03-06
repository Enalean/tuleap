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
import * as predefined_time_periods from "../helper/predefined-time-periods";
import type { PredefinedTimePeriod } from "../helper/predefined-time-periods";
import {
    CURRENT_WEEK,
    LAST_7_DAYS,
    LAST_MONTH,
    LAST_WEEK,
    TODAY,
    YESTERDAY,
} from "../helper/predefined-time-periods";
import type { Option } from "@tuleap/option";
import { usePersonalTimetrackingWidgetStore } from "../store/root";

vi.mock("tlp", () => ({
    datePicker: (): { setDate(): void } => ({
        setDate: (): void => {
            // Do nothing
        },
    }),
}));

let selected_time_period: Option<PredefinedTimePeriod>;

describe("Given a personal timetracking widget in writing mode", () => {
    function getWritingModeInstance(): VueWrapper {
        return shallowMount(WidgetWritingMode, {
            global: {
                ...getGlobalTestOptions({
                    initial_state: {
                        root: {
                            selected_time_period: selected_time_period,
                        },
                    },
                }),
            },
        });
    }

    it("When nothing is selected, then 'Last 7 days' should be selected", () => {
        const wrapper = getWritingModeInstance();

        expect(
            wrapper.find<HTMLSelectElement>("[data-test=timetracking-predefined-periods]").element
                .value,
        ).toBe(LAST_7_DAYS);
    });

    it("When Today is selected, then 'getTodayPeriod' should be called", () => {
        const getTodayPeriod = vi.spyOn(predefined_time_periods, "getTodayPeriod");
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        select.setValue(TODAY);

        const store = usePersonalTimetrackingWidgetStore();

        expect(store.selected_time_period.unwrapOr("")).toBe(TODAY);
        expect(getTodayPeriod).toHaveBeenCalledOnce();
    });

    it("When Yesterday is selected, then 'getYesterdayPeriod' should be called", () => {
        const getYesterdayPeriod = vi.spyOn(predefined_time_periods, "getYesterdayPeriod");
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        select.setValue(YESTERDAY);

        const store = usePersonalTimetrackingWidgetStore();

        expect(store.selected_time_period.unwrapOr("")).toBe(YESTERDAY);
        expect(getYesterdayPeriod).toHaveBeenCalledOnce();
    });

    it("When Last 7 days is selected, then 'getLastSevenDaysPeriod' should be called", () => {
        const getLastSevenDaysPeriod = vi.spyOn(predefined_time_periods, "getLastSevenDaysPeriod");
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        select.setValue(LAST_7_DAYS);

        const store = usePersonalTimetrackingWidgetStore();

        expect(store.selected_time_period.unwrapOr("")).toBe(LAST_7_DAYS);
        expect(getLastSevenDaysPeriod).toHaveBeenCalledOnce();
    });

    it("When Current week is selected, then 'getCurrentWeekPeriod' should be called", () => {
        const getCurrentWeekPeriod = vi.spyOn(predefined_time_periods, "getCurrentWeekPeriod");
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        select.setValue(CURRENT_WEEK);

        const store = usePersonalTimetrackingWidgetStore();

        expect(store.selected_time_period.unwrapOr("")).toBe(CURRENT_WEEK);
        expect(getCurrentWeekPeriod).toHaveBeenCalledOnce();
    });

    it("When Last week is selected, then 'getLastWeekPeriod' should be called", () => {
        const getLastWeekPeriod = vi.spyOn(predefined_time_periods, "getLastWeekPeriod");
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        select.setValue(LAST_WEEK);

        const store = usePersonalTimetrackingWidgetStore();

        expect(store.selected_time_period.unwrapOr("")).toBe(LAST_WEEK);
        expect(getLastWeekPeriod).toHaveBeenCalledOnce();
    });

    it("When Last month is selected, then 'getLastMonthPeriod' should be called", () => {
        const getLastMonthPeriod = vi.spyOn(predefined_time_periods, "getLastMonthPeriod");
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        select.setValue(LAST_MONTH);

        const store = usePersonalTimetrackingWidgetStore();

        expect(store.selected_time_period.unwrapOr("")).toBe(LAST_MONTH);
        expect(getLastMonthPeriod).toHaveBeenCalledOnce();
    });

    it("When start date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        const input = wrapper.find<HTMLInputElement>("[data-test=timetracking-start-date]");
        await input.setValue("2024-03-01");

        const store = usePersonalTimetrackingWidgetStore();

        expect(select.element.value).toBe("");
        expect(store.selected_time_period.isNothing()).toBe(true);
    });

    it("When end date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWritingModeInstance();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=timetracking-predefined-periods]",
        );
        const input = wrapper.find<HTMLInputElement>("[data-test=timetracking-end-date]");
        await input.setValue("2024-03-31");

        const store = usePersonalTimetrackingWidgetStore();

        expect(select.element.value).toBe("");
        expect(store.selected_time_period.isNothing()).toBe(true);
    });
});
