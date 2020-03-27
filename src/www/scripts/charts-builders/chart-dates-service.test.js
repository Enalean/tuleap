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

import { getFormattedDates } from "./chart-dates-service.js";

describe("chartDatesServices", () => {
    describe("getFormattedDates", () => {
        it("When the dataset is formatted twice, Then dates are still good", () => {
            const dataset = getDataset();

            const dataset_modified_once = getFormattedDates(dataset);
            expect(dataset_modified_once[0].date).toEqual("2019-08-12");
            expect(dataset_modified_once[1].date).toEqual("2019-08-13");

            const dataset_modified_twice = getFormattedDates(dataset);
            expect(dataset_modified_twice[0].date).toEqual("2019-08-12");
            expect(dataset_modified_twice[1].date).toEqual("2019-08-13");
        });

        it("When the dataset is formatted, Then all attributes still exists and only dates change", () => {
            const dataset = getDatasetWithSomeAttributes();

            const dataset_modified = getFormattedDates(dataset);

            expect(dataset_modified).toEqual(getDatasetWithSomeAttributesAndFormattedDates());
        });

        function getDataset() {
            return [
                { date: "2019-08-12T21:59:59+00:00", remaining_effort: 10 },
                { date: "2019-08-13T12:30:41+00:00", remaining_effort: 10 },
            ];
        }

        function getDatasetWithSomeAttributes() {
            return [
                {
                    date: "2019-08-12T21:59:59+00:00",
                    remaining_effort: 10,
                    total: 20,
                    progression: 30,
                },
                {
                    date: "2019-08-13T12:30:41+00:00",
                    remaining_effort: 100,
                    total: 500,
                    progression: 200,
                },
            ];
        }

        function getDatasetWithSomeAttributesAndFormattedDates() {
            return [
                { date: "2019-08-12", remaining_effort: 10, total: 20, progression: 30 },
                { date: "2019-08-13", remaining_effort: 100, total: 500, progression: 200 },
            ];
        }
    });
});
