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

import { describe, it, expect } from "vitest";
import {
    getTodayPeriod,
    getYesterdayPeriod,
    getCurrentWeekPeriod,
    getLastWeekPeriod,
    getLastSevenDaysPeriod,
    getLastMonthPeriod,
} from "./predefined-time-periods";

const today: Date = new Date();

describe("Predefined time periods", (): void => {
    describe("getTodayPeriod", (): void => {
        it("When I call this function, Then it should return the today's date for the start date and the end date", (): void => {
            const period_expected = {
                start: today.toISOString().split("T")[0],
                end: today.toISOString().split("T")[0],
            };

            const period_actual = getTodayPeriod();
            expect(period_actual.start.toISOString().split("T")[0]).toStrictEqual(
                period_expected.start,
            );
            expect(period_actual.end.toISOString().split("T")[0]).toStrictEqual(
                period_expected.end,
            );
        });
    });

    describe("getYesterdayPeriod", (): void => {
        it("When I call this function, then it should return the yesterday's date for the start date and the end date", (): void => {
            const yesterday = new Date();
            yesterday.setDate(today.getDate() - 1);
            const period_expected = {
                start: yesterday.toISOString().split("T")[0],
                end: yesterday.toISOString().split("T")[0],
            };

            const period_actual = getYesterdayPeriod();
            expect(period_actual.start.toISOString().split("T")[0]).toStrictEqual(
                period_expected.start,
            );
            expect(period_actual.end.toISOString().split("T")[0]).toStrictEqual(
                period_expected.end,
            );
        });
    });

    describe("getLastSevenDaysPeriod", (): void => {
        it("When I call this function, then the start date should be 7 days ago and the end date should be today", (): void => {
            const a_week_ago: Date = new Date();
            a_week_ago.setDate(a_week_ago.getDate() - 7);

            const period_expected = {
                start: a_week_ago.toISOString().split("T")[0],
                end: today.toISOString().split("T")[0],
            };

            const period_actual = getLastSevenDaysPeriod();
            expect(period_actual.start.toISOString().split("T")[0]).toStrictEqual(
                period_expected.start,
            );
            expect(period_actual.end.toISOString().split("T")[0]).toStrictEqual(
                period_expected.end,
            );
        });
    });

    describe("getCurrentWeekPeriod", (): void => {
        it("When I call this function, then the start date should be this monday and the end date should be next sunday", (): void => {
            const monday: Date = new Date();
            monday.setDate(today.getDate() + 1 - today.getDay());

            const next_sunday: Date = new Date();
            next_sunday.setDate(monday.getDate() + 6);

            const period_expected = {
                start: monday.toISOString().split("T")[0],
                end: next_sunday.toISOString().split("T")[0],
            };

            const period_actual = getCurrentWeekPeriod();
            expect(period_actual.start.toISOString().split("T")[0]).toStrictEqual(
                period_expected.start,
            );
            expect(period_actual.end.toISOString().split("T")[0]).toStrictEqual(
                period_expected.end,
            );
        });
    });

    describe("getLastWeekPeriod", (): void => {
        it("When I call this function, then the start date should be last monday and the end date should be last sunday", (): void => {
            const days_since_last_monday = 1 - today.getDay();
            const last_monday: Date = new Date();
            last_monday.setDate(today.getDate() + days_since_last_monday - 7);

            const days_until_sunday = 7 - today.getDay();
            const last_sunday: Date = new Date();
            last_sunday.setDate(today.getDate() + days_until_sunday - 7);

            const period_expected = {
                start: last_monday.toISOString().split("T")[0],
                end: last_sunday.toISOString().split("T")[0],
            };

            const period_actual = getLastWeekPeriod();
            expect(period_actual.start.toISOString().split("T")[0]).toStrictEqual(
                period_expected.start,
            );
            expect(period_actual.end.toISOString().split("T")[0]).toStrictEqual(
                period_expected.end,
            );
        });
    });

    describe("getLastMonthPeriod", (): void => {
        it("When I call this function, then the start date should be the first day of last month and the end date should be last day of last month", (): void => {
            const start_last_month = new Date();
            start_last_month.setMonth(today.getMonth() - 1, 1);

            const start_this_month = new Date();
            start_this_month.setMonth(today.getMonth(), 1);

            const end_last_month = new Date();
            end_last_month.setMonth(start_this_month.getMonth());
            end_last_month.setDate(start_this_month.getDate() - 1);

            const period_expected = {
                start: start_last_month.toISOString().split("T")[0],
                end: end_last_month.toISOString().split("T")[0],
            };

            const period_actual = getLastMonthPeriod();
            expect(period_actual.start.toISOString().split("T")[0]).toStrictEqual(
                period_expected.start,
            );
            expect(period_actual.end.toISOString().split("T")[0]).toStrictEqual(
                period_expected.end,
            );
        });
    });
});
