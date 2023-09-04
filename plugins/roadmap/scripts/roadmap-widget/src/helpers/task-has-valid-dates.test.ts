/**
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
import type { Task } from "../type";
import { doesTaskHaveEndDateGreaterOrEqualToStartDate } from "./task-has-valid-dates";

describe("doesTaskHaveEndDateGreaterOrEqualToStartDate", () => {
    it("Returns true if no start date", () => {
        expect(doesTaskHaveEndDateGreaterOrEqualToStartDate({ start: null } as Task)).toBe(true);
    });

    it("Returns true if no end date", () => {
        expect(
            doesTaskHaveEndDateGreaterOrEqualToStartDate({
                start: new Date("2020-04-14T22:00:00.000Z"),
                end: null,
            } as Task),
        ).toBe(true);
    });

    it("Returns true if end date = start date", () => {
        expect(
            doesTaskHaveEndDateGreaterOrEqualToStartDate({
                start: new Date("2020-04-14T22:00:00.000Z"),
                end: new Date("2020-04-14T22:00:00.000Z"),
            } as Task),
        ).toBe(true);
    });

    it("Returns true if end date > start date", () => {
        expect(
            doesTaskHaveEndDateGreaterOrEqualToStartDate({
                start: new Date("2020-04-14T22:00:00.000Z"),
                end: new Date("2020-04-16T22:00:00.000Z"),
            } as Task),
        ).toBe(true);
    });

    it("Returns false if no start date and no end date", () => {
        expect(
            doesTaskHaveEndDateGreaterOrEqualToStartDate({
                start: null,
                end: null,
            } as Task),
        ).toBe(false);
    });

    it("Returns false if end date < start date", () => {
        expect(
            doesTaskHaveEndDateGreaterOrEqualToStartDate({
                start: new Date("2020-04-14T22:00:00.000Z"),
                end: new Date("2020-04-10T22:00:00.000Z"),
            } as Task),
        ).toBe(false);
    });
});
