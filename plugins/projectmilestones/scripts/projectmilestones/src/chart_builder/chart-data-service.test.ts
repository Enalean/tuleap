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

import { PointsWithDateForBurndown } from "../type";
import { getDisplayableData, getDisplayableDataForBurnup } from "./chart-data-service";
import { PointsWithDateForGenericBurnup } from "../../../../../agiledashboard/scripts/burnup-chart/src/type";

describe("chartDataService", () => {
    describe("getDisplayableData", () => {
        it("Get only data without empty remaining effort", () => {
            const points = getDisplayableData(getPointsWithDateWithMaxIs15());
            expect(points.length).toEqual(2);
            expect(points[0].remaining_effort).toEqual(10);
            expect(points[1].remaining_effort).toEqual(15);
        });
    });

    describe("getDisplayableDataForBurnup", () => {
        it("Get only data without empty total and progression", () => {
            const points = getDisplayableDataForBurnup(getPointsWithDateForGenericBurnup());
            expect(points.length).toEqual(1);
            expect(points[0].total).toEqual(40);
            expect(points[0].progression).toEqual(30);
        });
    });

    function getPointsWithDateWithMaxIs15(): PointsWithDateForBurndown[] {
        return [
            { date: "2019-07-01T00:00:00+00:00", remaining_effort: null },
            { date: "2019-07-02T00:00:00+00:00", remaining_effort: 10 },
            { date: "2019-07-03T00:00:00+00:00", remaining_effort: null },
            { date: "2019-07-04T00:00:00+00:00", remaining_effort: 15 },
        ];
    }

    function getPointsWithDateForGenericBurnup(): PointsWithDateForGenericBurnup[] {
        return [
            { date: "2019-07-01T00:00:00+00:00", total: null, progression: null },
            { date: "2019-07-02T00:00:00+00:00", total: null, progression: 10 },
            { date: "2019-07-03T00:00:00+00:00", total: 15, progression: null },
            { date: "2019-07-04T00:00:00+00:00", total: 40, progression: 30 },
        ];
    }
});
