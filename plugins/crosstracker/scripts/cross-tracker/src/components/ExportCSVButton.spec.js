/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import { createStore } from "../store/index.js";
import ExportCSVButton from "./ExportCSVButton.vue";
import { rewire$getCSVReport, restore as restoreRest } from "../api/rest-querier.js";
import { rewire$download, restore as restoreDownload } from "../helpers/download-helper.js";

describe("ExportCSVButton", () => {
    let download, getCSVReport;
    beforeEach(() => {
        download = jasmine.createSpy("download");
        rewire$download(download);
        getCSVReport = jasmine.createSpy("getCSVReport");
        rewire$getCSVReport(getCSVReport);
    });

    afterEach(() => {
        restoreRest();
        restoreDownload();
    });

    function instantiateComponent() {
        const Component = Vue.extend(ExportCSVButton);
        const vm = new Component({
            store: createStore()
        });
        spyOn(vm.$store, "commit");
        return vm;
    }

    describe("exportCSV()", () => {
        it("When the server responds, then it will hide feedbacks, show a spinner and offer to download a CSV file with the results", async () => {
            const vm = instantiateComponent();
            vm.$store.replaceState({
                report_id: 36
            });
            const csv = `"id"\r\n72\r\n17\r\n`;
            getCSVReport.and.returnValue(Promise.resolve(csv));

            const promise = vm.exportCSV();

            expect(vm.is_loading).toBe(true);
            await promise;

            expect(vm.$store.commit).toHaveBeenCalledWith("resetFeedbacks");
            expect(getCSVReport).toHaveBeenCalledWith(36);
            expect(download).toHaveBeenCalledWith(csv, "export-36.csv", "text/csv;encoding:utf-8");
            expect(vm.is_loading).toBe(false);
        });

        it("When there is a REST error, then it will be shown", async () => {
            const vm = instantiateComponent();
            getCSVReport.and.returnValue(
                Promise.reject({
                    response: {
                        status: 404,
                        text: () => Promise.resolve("Report with id 90 not found")
                    }
                })
            );

            await vm.exportCSV();

            expect(vm.is_loading).toBe(false);
            expect(vm.$store.commit).toHaveBeenCalledWith(
                "setErrorMessage",
                "Report with id 90 not found"
            );
        });

        it("When there is a 50x REST error, then a generic error message will be shown", async () => {
            const vm = instantiateComponent();
            getCSVReport.and.returnValue(
                Promise.reject({
                    response: {
                        status: 503
                    }
                })
            );

            await vm.exportCSV();

            expect(vm.is_loading).toBe(false);
            expect(vm.$store.commit).toHaveBeenCalledWith("setErrorMessage", jasmine.any(String));
        });
    });
});
