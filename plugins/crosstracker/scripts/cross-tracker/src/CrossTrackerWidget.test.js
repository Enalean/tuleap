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

import { localVue } from "./helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import { mockFetchError } from "../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import { createStoreMock } from "../../../../../src/scripts/vue-components/store-wrapper-jest";
import { createStore } from "./store/index.js";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import BackendCrossTrackerReport from "./backend-cross-tracker-report.js";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report.js";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report.js";
import * as rest_querier from "./api/rest-querier.js";

describe("CrossTrackerWidget", () => {
    let backendCrossTrackerReport, readingCrossTrackerReport, writingCrossTrackerReport, getReport;

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
    });

    function instantiateComponent(state) {
        let defaulted_state = state;
        if (!state) {
            defaulted_state = {
                is_user_admin: false,
                invalid_trackers: [],
            };
        }
        return shallowMount(CrossTrackerWidget, {
            localVue,
            propsData: {
                backendCrossTrackerReport,
                readingCrossTrackerReport,
                writingCrossTrackerReport,
            },
            mocks: {
                $store: createStoreMock(createStore(), defaulted_state),
            },
        });
    }

    describe("switchToWritingMode() -", () => {
        it(`when I switch to the writing mode,
            then the writing report will be updated and a mutation will be committed`, () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue[{ id: 102 }];
            const wrapper = instantiateComponent({
                is_user_admin: true,
                invalid_trackers: [],
            });
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");

            wrapper.vm.switchToWritingMode();

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("switchToWritingMode");
        });

        it(`Given I am not admin,
            when I try to switch to writing mode, then nothing will happen`, () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue([]);
            const wrapper = instantiateComponent();
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");

            wrapper.vm.switchToWritingMode();

            expect(duplicate).not.toHaveBeenCalled();
            expect(wrapper.vm.$store.commit).not.toHaveBeenCalledWith("switchToWritingMode");
        });
    });

    describe("switchToReadingMode() -", () => {
        it(`When I switch to the reading mode with saved state,
            then the writing report will be updated and a mutation will be committed`, () => {
            const wrapper = instantiateComponent();
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");

            const payload = { saved_state: true };
            wrapper.vm.switchToReadingMode(payload);

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("switchToReadingMode", payload);
        });

        it(`When I switch to the reading mode with unsaved state,
            then a batch of artifacts will be loaded,
            the reading report will be updated and a mutation will be committed`, () => {
            const wrapper = instantiateComponent();
            const duplicate = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");

            const payload = { saved_state: false };
            wrapper.vm.switchToReadingMode(payload);

            expect(duplicate).toHaveBeenCalledWith(writingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("switchToReadingMode", payload);
        });
    });

    describe("loadBackendReport() -", () => {
        beforeEach(() => {
            getReport = jest.spyOn(rest_querier, "getReport");
        });

        it("When I load the report, then the reports will be initialized", async () => {
            const trackers = [{ id: 25 }, { id: 30 }];
            const expert_query = '@title != ""';
            getReport.mockResolvedValue({ trackers, expert_query });
            jest.spyOn(backendCrossTrackerReport, "init").mockImplementation(() => {});
            const wrapper = instantiateComponent();
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");

            const promise = wrapper.vm.loadBackendReport();
            expect(wrapper.vm.is_loading).toBe(true);

            await promise;

            expect(wrapper.vm.is_loading).toBe(false);
            expect(backendCrossTrackerReport.init).toHaveBeenCalledWith(trackers, expert_query);
            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
        });

        it("When there is a REST error, it will be shown", () => {
            const message = "Report 41 not found";
            mockFetchError(getReport, {
                error_json: {
                    error: { message },
                },
            });
            const wrapper = instantiateComponent();

            return wrapper.vm.loadBackendReport().then(() => {
                expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setErrorMessage", message);
            });
        });
    });

    describe("reportSaved() -", () => {
        it(`when the report is saved,
            then the reports will be updated and a mutation will be committed`, () => {
            const wrapper = instantiateComponent();
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");

            wrapper.vm.reportSaved();

            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
                "switchReportToSaved",
                expect.any(String)
            );
        });
    });
});
