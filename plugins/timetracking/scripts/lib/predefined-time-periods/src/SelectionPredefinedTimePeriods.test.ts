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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { MockInstance } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type {
    HostElement,
    InternalPredefinedTimePeriodSelect,
    PeriodOption,
} from "./SelectionPredefinedTimePeriods";
import { resetSelection, renderContent, onChange } from "./SelectionPredefinedTimePeriods";
import type { PredefinedTimePeriod } from "./predefined-time-periods";
import {
    CURRENT_WEEK,
    LAST_7_DAYS,
    LAST_MONTH,
    LAST_WEEK,
    TODAY,
    YESTERDAY,
} from "./predefined-time-periods";

import * as time_periods from "./predefined-time-periods";

describe("SelectionPredefinedTimePeriods", () => {
    let target: ShadowRoot, doc: Document, onselection: MockInstance;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        onselection = vi.fn();
    });

    it("When the select value changes, Then it should trigger the onChange callback", () => {
        const host = {
            onChange: vi.fn(),
        } as unknown as HostElement;

        const update = renderContent(host);
        update(host, target);

        const select = selectOrThrow(
            target,
            "[data-test=predefined-periods-select]",
            HTMLSelectElement,
        );

        const today_option = select.options[1];

        today_option.selected = true;
        select.dispatchEvent(new Event("change", { bubbles: true }));

        expect(host.onChange).toHaveBeenCalledOnce();
    });

    const getSelectElement = (
        selected_time_period: PredefinedTimePeriod | "",
    ): HTMLSelectElement => {
        const select_element = doc.createElement("select");
        const selected_option = doc.createElement("option");

        selected_option.value = selected_time_period;
        selected_option.selected = true;

        select_element.append(selected_option);

        return select_element;
    };

    const getHost = (select_element: HTMLSelectElement): InternalPredefinedTimePeriodSelect => {
        return {
            select_element,
            onselection,
        } as unknown as InternalPredefinedTimePeriodSelect;
    };

    const getPeriodOption = (): PeriodOption => {
        const period_option = onselection.mock.calls[0][1];
        if (!("unwrapOr" in period_option)) {
            throw new Error("Expected an option");
        }
        return period_option;
    };

    describe("onChange", () => {
        type PeriodComputationFunctionName =
            | "getTodayPeriod"
            | "getYesterdayPeriod"
            | "getLastSevenDaysPeriod"
            | "getCurrentWeekPeriod"
            | "getLastWeekPeriod"
            | "getLastMonthPeriod";

        it.each([
            [TODAY, "getTodayPeriod" as PeriodComputationFunctionName],
            [YESTERDAY, "getYesterdayPeriod" as PeriodComputationFunctionName],
            [LAST_7_DAYS, "getLastSevenDaysPeriod" as PeriodComputationFunctionName],
            [CURRENT_WEEK, "getCurrentWeekPeriod" as PeriodComputationFunctionName],
            [LAST_WEEK, "getLastWeekPeriod" as PeriodComputationFunctionName],
            [LAST_MONTH, "getLastMonthPeriod" as PeriodComputationFunctionName],
        ])(
            "When the selected value is %s, then it should call the onselection callback with the correct PredefinedTimePeriod and Period",
            (
                selected_time_period,
                expected_period_computation_function: PeriodComputationFunctionName,
            ) => {
                const select_element = getSelectElement(selected_time_period);
                const host = getHost(select_element);
                const period = { start: new Date(), end: new Date() };
                const spy = vi
                    .spyOn(time_periods, expected_period_computation_function)
                    .mockReturnValue(period);

                onChange(host);

                const period_option = getPeriodOption();

                expect(spy).toHaveBeenCalledOnce();
                expect(onselection).toHaveBeenCalledOnce();
                expect(onselection).toHaveBeenCalledWith(selected_time_period, period_option);
                expect(period_option.unwrapOr(null)).toStrictEqual(period);
            },
        );

        it("When the selected value is 'Please choose...', then it should call the onselection callback with the correct PredefinedTimePeriod and without Period", () => {
            const select_element = getSelectElement("");
            const host = getHost(select_element);

            onChange(host);

            const period_option = getPeriodOption();

            expect(onselection).toHaveBeenCalledOnce();
            expect(onselection).toHaveBeenCalledWith("", period_option);
            expect(period_option.isNothing()).toBeTruthy();
        });
    });

    describe("resetSelection", () => {
        it("When resetSelection is called, then it should call the onselection callback with the correct PredefinedTimePeriod and without Period, and the select should be reinitialize", () => {
            const select_element = getSelectElement(TODAY);
            const host = getHost(select_element);

            resetSelection(host)();

            expect(onselection).toHaveBeenCalledOnce();

            const period_option = getPeriodOption();
            expect(onselection).toHaveBeenCalledWith("", period_option);
            expect(period_option.isNothing()).toBeTruthy();
        });
    });
});
