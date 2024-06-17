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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { getGlobalTestOptions } from "./helpers/global-options-for-tests";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import BackendCrossTrackerReport from "./backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import * as rest_querier from "./api/rest-querier";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import type { InvalidTracker, State, TrackerAndProject } from "./type";

const noop = (): void => {
    //Do nothing
};

vi.useFakeTimers();

describe("CrossTrackerWidget", () => {
    let backendCrossTrackerReport: BackendCrossTrackerReport,
        readingCrossTrackerReport: ReadingCrossTrackerReport,
        writingCrossTrackerReport: WritingCrossTrackerReport,
        switchToWritingModeSpy: Mock,
        switchToReadingModeSpy: Mock,
        setErrorMessageSpy: Mock,
        switchReportToSavedSpy: Mock;

    beforeEach(() => {
        backendCrossTrackerReport = new BackendCrossTrackerReport();
        readingCrossTrackerReport = new ReadingCrossTrackerReport();
        writingCrossTrackerReport = new WritingCrossTrackerReport();
        switchToWritingModeSpy = vi.fn();
        switchToReadingModeSpy = vi.fn();
        setErrorMessageSpy = vi.fn();
        switchReportToSavedSpy = vi.fn();

        vi.spyOn(rest_querier, "getReport").mockReturnValue(
            okAsync({
                trackers: [],
                expert_query: "",
                invalid_trackers: [],
            }),
        );
    });

    function getWrapper(
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
                setInvalidTrackers: noop,
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

    describe("switchToWritingMode()", () => {
        it(`when I switch to the writing mode,
            then the writing report will be updated and a mutation will be committed`, async () => {
            const duplicate = vi.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = getWrapper({
                is_user_admin: true,
                reading_mode: true,
            });
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(switchToWritingModeSpy).toHaveBeenCalled();
        });

        it(`Given I am not admin,
            when I try to switch to writing mode, then nothing will happen`, async () => {
            const duplicate = vi.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = getWrapper({
                is_user_admin: false,
                reading_mode: true,
            });
            await vi.runOnlyPendingTimersAsync();
            duplicate.mockReset(); // It is called once during onMounted

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(duplicate).not.toHaveBeenCalled();
            expect(switchToWritingModeSpy).not.toHaveBeenCalled();
        });
    });

    describe("switchToReadingMode() -", () => {
        it(`When I switch to the reading mode with saved state,
            then the writing report will be updated and a mutation will be committed`, async () => {
            const duplicate = vi.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            const wrapper = getWrapper({
                is_user_admin: true,
                reading_mode: false,
            });
            await vi.runOnlyPendingTimersAsync();

            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: true });

            expect(duplicate).toHaveBeenCalledWith(readingCrossTrackerReport);
            expect(switchToReadingModeSpy).toHaveBeenCalledWith(expect.any(Object), true);
        });

        it(`When I switch to the reading mode with unsaved state,
            then a batch of artifacts will be loaded,
            the reading report will be updated and a mutation will be committed`, async () => {
            const duplicate = vi.spyOn(readingCrossTrackerReport, "duplicateFromWritingReport");
            const wrapper = getWrapper({
                is_user_admin: true,
                reading_mode: false,
            });
            await vi.runOnlyPendingTimersAsync();

            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: false });

            expect(duplicate).toHaveBeenCalledWith(writingCrossTrackerReport);
            expect(switchToReadingModeSpy).toHaveBeenCalledWith(expect.any(Object), false);
        });
    });

    describe("loadBackendReport() -", () => {
        it("When I load the report, then the reports will be initialized", async () => {
            const first_tracker: TrackerAndProject = {
                tracker: { id: 25, label: "alveolitis" },
                project: { id: 182, label: "betide" },
            };
            const second_tracker: TrackerAndProject = {
                tracker: { id: 956, label: "Stephanoceros" },
                project: { id: 248, label: "methodic" },
            };
            const trackers = [first_tracker, second_tracker];
            const invalid_trackers = [{ id: 956 } as InvalidTracker];
            const expert_query = '@title != ""';
            vi.spyOn(rest_querier, "getReport").mockReturnValue(
                okAsync({ trackers, expert_query, invalid_trackers }),
            );
            const init = vi.spyOn(backendCrossTrackerReport, "init");
            const duplicateReading = vi.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = vi.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            getWrapper({ is_user_admin: true });
            await vi.runOnlyPendingTimersAsync();

            expect(init).toHaveBeenCalledWith(trackers, expert_query);
            expect(duplicateReading).toHaveBeenCalledWith(backendCrossTrackerReport);
            expect(duplicateWriting).toHaveBeenCalledWith(readingCrossTrackerReport);
        });

        it("When there is a REST error, it will be shown", async () => {
            const message = "Report 41 not found";
            vi.spyOn(rest_querier, "getReport").mockReturnValue(
                errAsync(Fault.fromMessage(message)),
            );
            getWrapper({ is_user_admin: true });
            await vi.runOnlyPendingTimersAsync();

            expect(setErrorMessageSpy).toHaveBeenCalledWith(expect.any(Object), message);
        });
    });

    describe("reportSaved() -", () => {
        it(`when the report is saved,
            then the reports will be updated and a mutation will be committed`, async () => {
            const wrapper = getWrapper({
                is_user_admin: true,
                reading_mode: true,
            });
            const duplicateReading = vi.spyOn(readingCrossTrackerReport, "duplicateFromReport");
            const duplicateWriting = vi.spyOn(writingCrossTrackerReport, "duplicateFromReport");
            await vi.runOnlyPendingTimersAsync();

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
