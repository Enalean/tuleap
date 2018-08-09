/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import Vue from "vue";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";

describe("WidgetArtifactTable", () => {
    let ArtifactTable;

    beforeEach(() => {
        ArtifactTable = Vue.extend(WidgetArtifactTable);
    });

    function instantiateComponent() {
        return new ArtifactTable().$mount();
    }

    describe("getFormattedTotalSum", () => {
        it("Given a collection of times aggregated by artifacts, then it should return the formatted sum of all the times' minutes", () => {
            const vm = instantiateComponent();

            vm.tracked_times = [
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    },
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    }
                ],
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    }
                ]
            ];

            const formatted_times = vm.getFormattedTotalSum();
            expect(formatted_times).toEqual("01:00");
        });
    });
});
