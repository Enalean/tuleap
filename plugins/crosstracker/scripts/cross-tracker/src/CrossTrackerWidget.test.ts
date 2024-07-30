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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { nextTick } from "vue";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { getGlobalTestOptions } from "./helpers/global-options-for-tests";
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import BackendCrossTrackerReport from "./backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-mode/reading-cross-tracker-report";
import WritingCrossTrackerReport from "./writing-mode/writing-cross-tracker-report";
import * as rest_querier from "./api/rest-querier";
import ReadingMode from "./reading-mode/ReadingMode.vue";
import WritingMode from "./writing-mode/WritingMode.vue";
import type { InvalidTracker, TrackerAndProject } from "./type";
import { IS_USER_ADMIN, REPORT_ID } from "./injection-symbols";

vi.useFakeTimers();

describe("CrossTrackerWidget", () => {
    let backend_cross_tracker_report: BackendCrossTrackerReport,
        reading_cross_tracker_report: ReadingCrossTrackerReport,
        writing_cross_tracker_report: WritingCrossTrackerReport,
        is_user_admin: boolean;

    beforeEach(() => {
        backend_cross_tracker_report = new BackendCrossTrackerReport();
        reading_cross_tracker_report = new ReadingCrossTrackerReport();
        writing_cross_tracker_report = new WritingCrossTrackerReport();
        is_user_admin = true;

        vi.spyOn(rest_querier, "getReport").mockReturnValue(
            okAsync({
                trackers: [],
                expert_query: "",
                invalid_trackers: [],
            }),
        );
    });

    function getWrapper(): VueWrapper<InstanceType<typeof CrossTrackerWidget>> {
        return shallowMount(CrossTrackerWidget, {
            props: {
                writing_cross_tracker_report,
                backend_cross_tracker_report,
                reading_cross_tracker_report,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPORT_ID.valueOf()]: 96,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                },
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it(`Given a saved report,
            when I switch to writing mode to modify it,
            then the report will be in "edit-query" state
            and the writing report will be updated
            and it will clear the feedback messages`, async () => {
            const duplicate = vi.spyOn(writing_cross_tracker_report, "duplicateFromReport");
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(wrapper.vm.report_state).toBe("edit-query");
            expect(duplicate).toHaveBeenCalledWith(reading_cross_tracker_report);
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
        });

        it(`Given I am not admin,
            when I try to switch to writing mode, then nothing will happen`, async () => {
            is_user_admin = false;
            const duplicate = vi.spyOn(writing_cross_tracker_report, "duplicateFromReport");
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();
            duplicate.mockReset(); // It is called once during onMounted

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(duplicate).not.toHaveBeenCalled();
        });
    });

    describe("switchToReadingMode()", () => {
        it(`Given I started to modify the report
            when I cancel,
            then the report will be back to its "report-saved" state
            and the writing report will be reset`, async () => {
            const duplicate = vi.spyOn(writing_cross_tracker_report, "duplicateFromReport");
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: true });

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(duplicate).toHaveBeenCalledWith(reading_cross_tracker_report);
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.isNothing()).toBe(true);
        });

        it(`Given I started to modify the report
            when I switch back to reading mode
            then the report will be in "result-preview" state
            and the reading report will be updated
            and it will clear the feedback messages`, async () => {
            const duplicate = vi.spyOn(reading_cross_tracker_report, "duplicateFromWritingReport");
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: false });

            expect(wrapper.vm.report_state).toBe("result-preview");
            expect(duplicate).toHaveBeenCalledWith(writing_cross_tracker_report);
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.isNothing()).toBe(true);
        });
    });

    describe("reportSaved()", () => {
        it(`when the report is saved,
            then the reports will be updated
            and it will set a success message`, async () => {
            const wrapper = getWrapper();
            const duplicateReading = vi.spyOn(reading_cross_tracker_report, "duplicateFromReport");
            const duplicateWriting = vi.spyOn(writing_cross_tracker_report, "duplicateFromReport");
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: false });
            await nextTick();
            wrapper.findComponent(ReadingMode).vm.$emit("saved");

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(duplicateReading).toHaveBeenCalledWith(backend_cross_tracker_report);
            expect(duplicateWriting).toHaveBeenCalledWith(reading_cross_tracker_report);
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.unwrapOr(null)).toStrictEqual(expect.any(String));
        });
    });

    describe(`unsavedReportDiscarded()`, () => {
        it(`Given a report that has been modified,
            when its changes are discarded,
            then it will restore the reading report and clear the feedback messages`, async () => {
            const wrapper = getWrapper();
            const duplicateReading = vi.spyOn(reading_cross_tracker_report, "duplicateFromReport");
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();
            wrapper
                .findComponent(WritingMode)
                .vm.$emit("switch-to-reading-mode", { saved_state: false });
            await nextTick();
            wrapper.findComponent(ReadingMode).vm.$emit("discard-unsaved-report");

            expect(wrapper.vm.report_state).toBe("report-saved");
            expect(duplicateReading).toHaveBeenCalledWith(backend_cross_tracker_report);
            expect(wrapper.vm.current_fault.isNothing()).toBe(true);
            expect(wrapper.vm.current_success.isNothing()).toBe(true);
        });
    });

    describe("loadBackendReport()", () => {
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
            const init = vi.spyOn(backend_cross_tracker_report, "init");
            const duplicateReading = vi.spyOn(reading_cross_tracker_report, "duplicateFromReport");
            const duplicateWriting = vi.spyOn(writing_cross_tracker_report, "duplicateFromReport");
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(init).toHaveBeenCalledWith(trackers, expert_query);
            expect(duplicateReading).toHaveBeenCalledWith(backend_cross_tracker_report);
            expect(duplicateWriting).toHaveBeenCalledWith(reading_cross_tracker_report);
        });

        it("When there is a REST error, it will be shown", async () => {
            vi.spyOn(rest_querier, "getReport").mockReturnValue(
                errAsync(Fault.fromMessage("Report 41 not found")),
            );
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.current_fault.unwrapOr(null)?.isReportRetrieval()).toBe(true);
        });
    });

    describe(`isCSVExportAllowed`, () => {
        it(`when the report state is not "report-saved", it does not allow CSV export`, async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            wrapper.findComponent(ReadingMode).vm.$emit("switch-to-writing-mode");
            await nextTick();

            expect(wrapper.vm.is_csv_export_allowed).toBe(false);
        });

        it(`when there was an error, it does not allow CSV export`, () => {
            const wrapper = getWrapper();
            wrapper.vm.current_fault = Option.fromValue(Fault.fromMessage("Ooops"));

            expect(wrapper.vm.is_csv_export_allowed).toBe(false);
        });

        it(`when user is NOT admin and there is no error,
            it allows CSV export`, () => {
            is_user_admin = false;
            const wrapper = getWrapper();

            expect(wrapper.vm.is_csv_export_allowed).toBe(true);
        });

        it(`when user is admin and there are invalid trackers selected in the report,
            it does not allow CSV export`, async () => {
            const invalid_trackers: ReadonlyArray<InvalidTracker> = [{ id: 315 } as InvalidTracker];
            vi.spyOn(rest_querier, "getReport").mockReturnValue(
                okAsync({ trackers: [], expert_query: "", invalid_trackers }),
            );

            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.is_csv_export_allowed).toBe(false);
        });

        it(`when user is admin and there are no invalid trackers,
            it allows CSV export`, () => {
            const wrapper = getWrapper();

            expect(wrapper.vm.is_csv_export_allowed).toBe(true);
        });
    });
});
