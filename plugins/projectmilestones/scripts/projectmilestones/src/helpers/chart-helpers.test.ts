/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { ArtifactMilestone, BurndownData, BurnupData } from "../type";
import { getBurndownDataFromType, getBurnupDataFromType } from "./chart-helper";

describe("Chart Helpers", () => {
    describe("getBurndownDataFromType", () => {
        it("When the type is burndown, Then a BurndownChart object is returned", () => {
            const burndown = getBurndownDataFromType(getArtifact());

            if (!burndown) {
                throw new Error("Burndown doesn't exist");
            }

            expect(burndown.label).toEqual("Burndown");
        });
    });

    describe("getBurnupDataFromType", () => {
        it("When the type is burnup, Then a BurnupData object id rendered", () => {
            const burnup = getBurnupDataFromType(getArtifact());

            if (!burnup) {
                throw new Error("Burnup doesn't exist");
            }

            expect(burnup.label).toEqual("Burnup");
        });
    });

    function getArtifact(): ArtifactMilestone {
        const burndown_data = {
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
        } as BurndownData;

        const label_burndown = "Burndown";

        const burnup_data = {
            start_date: new Date("2017-01-22T13:42:08+02:00").toDateString(),
        } as BurnupData;

        const label_burnup = "Burnup";

        return {
            values: [
                {
                    field_id: 10,
                    label: label_burndown,
                    value: burndown_data,
                    type: "burndown",
                },
                {
                    field_id: 122,
                    label: label_burnup,
                    value: burnup_data,
                    type: "burnup",
                },
            ],
        } as ArtifactMilestone;
    }
});
