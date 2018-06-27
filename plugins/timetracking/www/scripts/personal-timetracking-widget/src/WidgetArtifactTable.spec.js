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
import { tlp, mockFetchError } from "tlp-mocks";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";

describe("WidgetArtifactTable", () => {
    let ArtifactTable;

    beforeEach(() => {
        ArtifactTable = Vue.extend(WidgetArtifactTable);
    });

    function instantiateComponent(data = {}) {
        return new ArtifactTable({
            propsData: { ...data }
        }).$mount();
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

    describe("loadTimes - rest errors", () => {
        it("Given a rest error, When no json error message is received, Then a default message is set up in the component 's rest_error private property.", async () => {
            const vm = instantiateComponent({
                startDate: "2018-03-08",
                endDate: "2018-03-15"
            });

            mockFetchError(tlp.get, {});

            await vm.loadTimes();

            expect(vm.hasRestError).toBe(true);
            expect(vm.rest_error).toEqual("An error occured");
        });

        it("Given a rest error, When a json error message is received, Then the message is extracted in the component 's rest_error private property.", async () => {
            const vm = instantiateComponent({
                startDate: "2018-03-08",
                endDate: "2018-03-15"
            });

            mockFetchError(tlp.get, {
                error_json: {
                    error: {
                        code: 403,
                        message: "Forbidden"
                    }
                }
            });

            await vm.loadTimes();

            expect(vm.hasRestError).toBe(true);
            expect(vm.rest_error).toEqual("403 Forbidden");
        });
    });

    describe("loadMore", () => {
        it("When the query has been modified, then the load more variable are reininitialized", async () => {
            const vm = instantiateComponent({
                startDate: "2018-03-08",
                endDate: "2018-03-15"
            });

            vm.pagination_offset = 100;
            vm.tracked_times.length = 100;
            vm.isInReadingMode = true;
            vm.hasQueryChanged = true;

            await vm.$nextTick();

            expect(vm.pagination_offset).toEqual(0);
            expect(vm.tracked_times.length).toEqual(0);
        });
    });
});
