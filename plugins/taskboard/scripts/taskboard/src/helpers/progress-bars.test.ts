/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { getWidthPercentage } from "./progress-bars";
import { RemainingEffort } from "../type";

describe("progress bars helper", () => {
    it("Returns a width percentage", () => {
        expect(getWidthPercentage(10, { value: 5 } as RemainingEffort)).toEqual(50);
    });

    it("Returns a float percentage if the progress is not a round number", () => {
        expect(getWidthPercentage(33, { value: 22 } as RemainingEffort)).toEqual(33.33333333333333);
    });

    it("Returns 0 if the remaining effort is greater than the initial effort", () => {
        expect(getWidthPercentage(10, { value: 12 } as RemainingEffort)).toEqual(0);
    });

    it("Returns 100 if the remaining effort is lesser than 0", () => {
        expect(getWidthPercentage(10, { value: -5 } as RemainingEffort)).toEqual(100);
    });

    it("Returns 100 if the remaining effort equals 0", () => {
        expect(getWidthPercentage(10, { value: 0 } as RemainingEffort)).toEqual(100);
    });

    it("Returns 0 if the remaining effort field is null", () => {
        expect(getWidthPercentage(10, null)).toEqual(0);
    });

    it("Returns 0 if the remaining effort is null", () => {
        expect(getWidthPercentage(10, { value: null } as RemainingEffort)).toEqual(0);
    });

    it("Returns 0 if the initial effort equals 0", () => {
        expect(getWidthPercentage(0, { value: 5 } as RemainingEffort)).toEqual(0);
    });

    it("Returns 0 if the initial effort is null", () => {
        expect(getWidthPercentage(null, { value: 5 } as RemainingEffort)).toEqual(0);
    });
});
