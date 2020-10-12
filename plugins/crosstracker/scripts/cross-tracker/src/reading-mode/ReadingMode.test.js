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

import { shallowMount } from "@vue/test-utils";
import { localVue } from "../helpers/local-vue";
import { mockFetchError } from "../../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { createStore } from "../store/index.js";
import ReadingMode from "./ReadingMode.vue";
import BackendCrossTrackerReport from "../backend-cross-tracker-report.js";
import ReadingCrossTrackerReport from "./reading-cross-tracker-report.js";
import * as rest_querier from "../api/rest-querier.js";
import { createStoreMock } from "../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("ReadingMode", () => {
    let backendCrossTrackerReport, readingCrossTrackerReport, updateReport;

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
    });

    function instantiateComponent() {
        return shallowMount(ReadingMode, {
            localVue,
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
            },
            mocks: {
                $store: createStoreMock(createStore()),
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it("When I switch to the writing mode, then an event will be emitted", () => {
            const wrapper = instantiateComponent();
            wrapper.vm.$store.state.is_user_admin = true;

            wrapper.vm.switchToWritingMode();

            expect(wrapper.emitted("switch-to-writing-mode")).toBeTruthy();
        });

        it(`Given I am browsing as project member,
            when I try to switch to writing mode, nothing will happen`, () => {
            const wrapper = instantiateComponent();
            wrapper.vm.$store.state.is_user_admin = false;

            wrapper.vm.switchToWritingMode();

            expect(wrapper.emitted("switchToWritingMode")).toBeFalsy();
        });
    });

    describe("saveReport()", () => {
        beforeEach(() => {
            updateReport = jest.spyOn(rest_querier, "updateReport");
        });

        it(`When I save the report,
            the backend report will be updated and an event will be emitted`, async () => {
            const initBackend = jest
                .spyOn(backendCrossTrackerReport, "init")
                .mockImplementation(() => {});
            const duplicateBackend = jest.spyOn(backendCrossTrackerReport, "duplicateFromReport");
            const trackers = [{ id: 36 }, { id: 17 }];
            const expert_query = '@description != ""';
            updateReport.mockResolvedValue({ trackers, expert_query });
            const wrapper = instantiateComponent();

            const promise = wrapper.vm.saveReport();
            expect(wrapper.vm.is_loading).toBe(true);

            await promise;
            expect(duplicateBackend).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(initBackend).toHaveBeenCalledWith(trackers, expert_query);
            expect(updateReport).toHaveBeenCalled();
            expect(wrapper.emitted("saved")).toBeTruthy();
            expect(wrapper.vm.is_loading).toBe(false);
        });

        it("Given the report is in error, then nothing will happen", async () => {
            const wrapper = instantiateComponent();
            wrapper.vm.$store.getters.has_error_message = true;

            await wrapper.vm.saveReport();
            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", () => {
            const error_json = { error: { message: "Report not found" } };
            mockFetchError(updateReport, { error_json });
            const wrapper = instantiateComponent();

            return wrapper.vm.saveReport().then(() => {
                expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                    "setErrorMessage",
                    "Report not found"
                );
            });
        });
    });

    describe("cancelReport() -", () => {
        it("when I click on 'Cancel', then the reading report will be reset", () => {
            const duplicateReading = jest
                .spyOn(readingCrossTrackerReport, "duplicateFromReport")
                .mockImplementation(() => {});
            const wrapper = instantiateComponent();

            wrapper.vm.cancelReport();

            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("discardUnsavedReport");
        });
    });
});
