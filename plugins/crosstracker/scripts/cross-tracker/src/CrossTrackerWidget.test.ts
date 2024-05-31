/**
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "./helpers/global-options-for-tests";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import BackendCrossTrackerReport from "./backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import * as rest_querier from "./api/rest-querier";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type { InvalidTracker, State } from "./type";

const noop = (): void => {
    //Do nothing
};

jest.useFakeTimers();

describe("CrossTrackerWidget", () => {
    let backendCrossTrackerReport: BackendCrossTrackerReport,
        readingCrossTrackerReport: ReadingCrossTrackerReport,
        writingCrossTrackerReport: WritingCrossTrackerReport,
        getReport: jest.SpyInstance,
        switchToWritingModeSpy: jest.Mock,
        switchToReadingModeSpy: jest.Mock,
        setErrorMessageSpy: jest.Mock,
        switchReportToSavedSpy: jest.Mock;

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        switchToWritingModeSpy = jest.fn();
        switchToReadingModeSpy = jest.fn();
        setErrorMessageSpy = jest.fn();
        switchReportToSavedSpy = jest.fn();
    });

    function instantiateComponent(
        state: Partial<State>,
    ): VueWrapper<InstanceType<typeof CrossTrackerWidget>> {
        const invalid_trackers: ReadonlyArray<InvalidTracker> = [];
        const store_options = {
            state: {
                ...state,
                invalid_trackers,
            } as State,
            getters: { has_success_message: () => false },
            mutations: {
                switchToWritingMode: switchToWritingModeSpy,
                switchToReadingMode: switchToReadingModeSpy,
                setErrorMessage: setErrorMessageSpy,
                switchReportToSaved: switchReportToSavedSpy,
                resetInvalidTrackerList: noop,
            },
        };

        return shallowMount(CrossTrackerWidget, {
            props: {
                writingCrossTrackerReport,
                backendCrossTrackerReport,
                readingCrossTrackerReport,
            },
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    describe("switchToWritingMode() -", () => {
        it(`when I switch to the writing mode,
            then the writing report will be updated and a mutation will be committed`, async () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue([
                { id: 102 } as ProjectReference,
            ]);
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = instantiateComponent({
                is_user_admin: true,
                reading_mode: true,
            });
            await jest.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(switchToWritingModeSpy).toHaveBeenCalled();
        });

        it(`Given I am not admin,
            when I try to switch to writing mode, then nothing will happen`, async () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockResolvedValue([]);
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = instantiateComponent({
                is_user_admin: false,
                reading_mode: true,
            });
            await jest.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(duplicate).not.toHaveBeenCalled();
            expect(switchToWritingModeSpy).not.toHaveBeenCalled();
        });
    });

    describe("switchToReadingMode() -", () => {
        it(`When I switch to the reading mode with saved state,
            then the writing report will be updated and a mutation will be committed`, async () => {
            const duplicate = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = instantiateComponent({
                is_user_admin: true,
                reading_mode: false,
            });
            await jest.runOnlyPendingTimersAsync();

            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: true });

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(switchToReadingModeSpy).toHaveBeenCalledWith(expect.any(Object), true);
        });

        it(`When I switch to the reading mode with unsaved state,
            then a batch of artifacts will be loaded,
            the reading report will be updated and a mutation will be committed`, async () => {
            const duplicate = jest.spyOn(readingCrossTrackerReport, "duplicateFromWritingReport");
            const wrapper = instantiateComponent({
                is_user_admin: true,
                reading_mode: false,
            });
            await jest.runOnlyPendingTimersAsync();

            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: false });

            expect(duplicate).toHaveBeenCalledWith(writingCrossTrackerReport);
            expect(switchToReadingModeSpy).toHaveBeenCalledWith(expect.any(Object), false);
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
            jest.spyOn(backendCrossTrackerReport, "init").mockImplementation(() => {
                // nothing to mock
            });
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            instantiateComponent({ is_user_admin: true });
            await jest.runOnlyPendingTimersAsync();

            expect(backendCrossTrackerReport.init).toHaveBeenCalledWith(trackers, expert_query);
            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
        });

        it("When there is a REST error, it will be shown", async () => {
            const message = "Report 41 not found";
            mockFetchError(getReport, {
                error_json: {
                    error: { message },
                },
            });
            instantiateComponent({ is_user_admin: true });
            await jest.runOnlyPendingTimersAsync();

            expect(setErrorMessageSpy).toHaveBeenCalledWith(expect.any(Object), message);
        });
    });

    describe("reportSaved() -", () => {
        it(`when the report is saved,
            then the reports will be updated and a mutation will be committed`, async () => {
            const wrapper = instantiateComponent({
                is_user_admin: true,
                reading_mode: true,
            });
            const duplicateReading = jest.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = jest.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            await jest.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("saved");

            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(switchReportToSavedSpy).toHaveBeenCalledWith(
                expect.any(Object),
                expect.any(String),
            );
        });
    });
});
