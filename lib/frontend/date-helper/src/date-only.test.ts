/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { formatDateYearMonthDay } from "./date-only";

describe("formatDateYearMonthDay", () => {
    it("Given date, When I call this function with an ISO date, then it should return date at good format", () => {
        const date_iso = new Date("2017-01-22T13:42:08+02:00");
        expect(formatDateYearMonthDay("en-US", date_iso.toDateString())).toBe("Jan 22, 2017");
    });

    it("When I call this function with null, then it should return empty string", () => {
        expect(formatDateYearMonthDay("en-US", null)).toBe("");
    });

    it("When I call this function with an empty string, then it should return empty string", () => {
        expect(formatDateYearMonthDay("en-US", "")).toBe("");
    });
});
