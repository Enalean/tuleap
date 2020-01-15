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

import { PointsWithDate } from "../type";
import { getDisplayableData } from "./chart-data-service";

describe("chartDataService", () => {
    describe("getDisplayableData", () => {
        it("Get only data without empty remaining effort", () => {
            const points = getDisplayableData(getPointsWithDateWithMaxIs15());
            expect(points.length).toEqual(2);
            expect(points[0].remaining_effort).toEqual(10);
            expect(points[1].remaining_effort).toEqual(15);
        });
    });

    function getPointsWithDateWithMaxIs15(): PointsWithDate[] {
        return [
            { date: "2019-07-01T00:00:00+00:00", remaining_effort: null },
            { date: "2019-07-02T00:00:00+00:00", remaining_effort: 10 },
            { date: "2019-07-03T00:00:00+00:00", remaining_effort: null },
            { date: "2019-07-04T00:00:00+00:00", remaining_effort: 15 }
        ];
    }
});
