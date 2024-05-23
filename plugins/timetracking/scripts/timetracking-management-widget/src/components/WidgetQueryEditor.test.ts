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

import { describe, it, expect, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import {
    CURRENT_WEEK,
    LAST_7_DAYS,
    LAST_MONTH,
    LAST_WEEK,
    TODAY,
    YESTERDAY,
} from "@tuleap/plugin-timetracking-predefined-time-periods";
import * as predefined_time_periods from "@tuleap/plugin-timetracking-predefined-time-periods";

vi.mock("tlp", () => ({
    datePicker: (): { setDate(): void } => ({
        setDate: (): void => {
            // Do nothing
        },
    }),
}));

describe("Given a timetracking management widget query editor", () => {
    const start_date_test = "2024-05-22";
    const end_date_test = "2024-05-22";
    const predefined_time_selected = TODAY;

    function getWidgetQueryEditorInstance(): VueWrapper {
        return shallowMount(WidgetQueryEditor, {
            props: {
                start_date: start_date_test,
                end_date: end_date_test,
                predefined_time_selected: predefined_time_selected,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it("When the submit button is clicked, dates are updated and the edition mode is closed", () => {
        const wrapper = getWidgetQueryEditorInstance();
        const start_date_input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        const end_date_input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");

        start_date_input.setValue("2024-05-10");
        end_date_input.setValue("2024-05-20");
        wrapper.find("[data-test=search-button]").trigger("click");

        const set_dates_event = wrapper.emitted("setDates");
        const close_edit_mode_event = wrapper.emitted("closeEditMode");

        expect(set_dates_event).toBeDefined();
        if (set_dates_event) {
            expect(set_dates_event[0]).toStrictEqual([
                start_date_input.element.value,
                end_date_input.element.value,
                "",
            ]);
        }

        expect(close_edit_mode_event).toBeDefined();
    });

    it("When the cancel button is clicked, dates aren't updated and the edition mode is closed", () => {
        const wrapper = getWidgetQueryEditorInstance();

        wrapper.find("[data-test=cancel-button]").trigger("click");

        const set_dates_event = wrapper.emitted("setDates");
        const close_edit_mode_event = wrapper.emitted("closeEditMode");

        expect(set_dates_event).toBeUndefined();
        expect(close_edit_mode_event).toBeDefined();
    });

    it("When Today is selected, then 'getTodayPeriod' should be called", () => {
        const getTodayPeriod = vi.spyOn(predefined_time_periods, "getTodayPeriod");
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        select.setValue(TODAY);

        expect(getTodayPeriod).toHaveBeenCalledOnce();
    });

    it("When Yesterday is selected, then 'getYesterdayPeriod' should be called", () => {
        const getYesterdayPeriod = vi.spyOn(predefined_time_periods, "getYesterdayPeriod");
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        select.setValue(YESTERDAY);

        expect(getYesterdayPeriod).toHaveBeenCalledOnce();
    });

    it("When Last 7 days is selected, then 'getLastSevenDaysPeriod' should be called", () => {
        const getLastSevenDaysPeriod = vi.spyOn(predefined_time_periods, "getLastSevenDaysPeriod");
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        select.setValue(LAST_7_DAYS);

        expect(getLastSevenDaysPeriod).toHaveBeenCalledOnce();
    });

    it("When Current week is selected, then 'getCurrentWeekPeriod' should be called", () => {
        const getCurrentWeekPeriod = vi.spyOn(predefined_time_periods, "getCurrentWeekPeriod");
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        select.setValue(CURRENT_WEEK);

        expect(getCurrentWeekPeriod).toHaveBeenCalledOnce();
    });

    it("When Last week is selected, then 'getLastWeekPeriod' should be called", () => {
        const getLastWeekPeriod = vi.spyOn(predefined_time_periods, "getLastWeekPeriod");
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        select.setValue(LAST_WEEK);

        expect(getLastWeekPeriod).toHaveBeenCalledOnce();
    });

    it("When Last month is selected, then 'getLastMonthPeriod' should be called", () => {
        const getLastMonthPeriod = vi.spyOn(predefined_time_periods, "getLastMonthPeriod");
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        select.setValue(LAST_MONTH);

        expect(getLastMonthPeriod).toHaveBeenCalledOnce();
    });

    it("When start date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        const input = wrapper.find<HTMLInputElement>("[data-test=start-date-input]");
        await input.setValue("2024-23-05");

        expect(select.element.value).toBe("");
    });

    it("When end date is selected manually, then the selected predefined time period should be cleared", async () => {
        const wrapper = getWidgetQueryEditorInstance();
        const select = wrapper.find<HTMLSelectElement>("[data-test=predefined-periods-select]");
        const input = wrapper.find<HTMLInputElement>("[data-test=end-date-input]");
        await input.setValue("2024-23-05");

        expect(select.element.value).toBe("");
    });
});
