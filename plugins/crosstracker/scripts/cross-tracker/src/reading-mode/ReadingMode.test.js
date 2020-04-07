/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
import GetTextPlugin from "vue-gettext";
import { mockFetchError } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { createStore } from "../store/index.js";
import ReadingMode from "./ReadingMode.vue";
import BackendCrossTrackerReport from "../backend-cross-tracker-report.js";
import ReadingCrossTrackerReport from "./reading-cross-tracker-report.js";
import * as rest_querier from "../api/rest-querier.js";

describe("ReadingMode", () => {
    let ReadingModeElement, backendCrossTrackerReport, readingCrossTrackerReport, updateReport;

    beforeEach(() => {
        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });
        ReadingModeElement = Vue.extend(ReadingMode);
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
    });

    function instantiateComponent() {
        const vm = new ReadingModeElement({
            store: createStore(),
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
            },
        });
        vm.$mount();

        return vm;
    }

    describe("switchToWritingMode() -", () => {
        it("When I switch to the writing mode, then an event will be emitted", () => {
            const vm = instantiateComponent();
            jest.spyOn(vm, "$emit").mockImplementation(() => {});
            vm.$store.replaceState({
                is_user_admin: true,
            });

            vm.switchToWritingMode();

            expect(vm.$emit).toHaveBeenCalledWith("switchToWritingMode");
        });

        it("Given I am browsing as project member, when I try to switch to writing mode, nothing will happen", () => {
            const vm = instantiateComponent();
            vm.$store.replaceState({
                is_user_admin: false,
            });
            jest.spyOn(vm, "$emit").mockImplementation(() => {});

            vm.switchToWritingMode();

            expect(vm.$emit).not.toHaveBeenCalled();
        });
    });

    describe("saveReport() -", () => {
        beforeEach(() => {
            updateReport = jest.spyOn(rest_querier, "updateReport");
        });

        it("When I save the report, the backend report will be updated and an event will be emitted", async () => {
            jest.spyOn(backendCrossTrackerReport, "init").mockImplementation(() => {});
            jest.spyOn(
                backendCrossTrackerReport,
                "duplicateFromReport"
            ).mockImplementation(() => {});
            const trackers = [{ id: 36 }, { id: 17 }];
            const expert_query = '@description != ""';
            updateReport.mockImplementation(() =>
                Promise.resolve({
                    trackers,
                    expert_query,
                })
            );
            const vm = instantiateComponent();
            jest.spyOn(vm, "$emit").mockImplementation(() => {});

            const promise = vm.saveReport();
            expect(vm.is_loading).toBe(true);

            await promise;
            expect(backendCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                readingCrossTrackerReport
            );
            expect(backendCrossTrackerReport.init).toHaveBeenCalledWith(trackers, expert_query);
            expect(updateReport).toHaveBeenCalled();
            expect(vm.$emit).toHaveBeenCalledWith("saved");
            expect(vm.is_loading).toBe(false);
        });

        it("Given the report is in error, then nothing will happen", async () => {
            const vm = instantiateComponent();
            vm.$store.replaceState({
                error_message: "Error",
            });

            await vm.saveReport();
            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", () => {
            const error_json = {
                error: {
                    message: "Report not found",
                },
            };
            mockFetchError(updateReport, { error_json });
            const vm = instantiateComponent();
            jest.spyOn(vm.$store, "commit").mockImplementation(() => {});

            return vm.saveReport().then(() => {
                expect(vm.$store.commit).toHaveBeenCalledWith(
                    "setErrorMessage",
                    "Report not found"
                );
            });
        });
    });

    describe("cancelReport() -", () => {
        it("when I click on 'Cancel', then the reading report will be reset", () => {
            jest.spyOn(
                readingCrossTrackerReport,
                "duplicateFromReport"
            ).mockImplementation(() => {});
            const vm = instantiateComponent();
            jest.spyOn(vm.$store, "commit").mockImplementation(() => {});

            vm.cancelReport();

            expect(readingCrossTrackerReport.duplicateFromReport).toHaveBeenCalledWith(
                backendCrossTrackerReport
            );
            expect(vm.$store.commit).toHaveBeenCalledWith("discardUnsavedReport");
        });
    });
});
