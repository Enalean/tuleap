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
import { ref } from "vue";
import { Fault } from "@tuleap/fault";
import ReadingMode from "./ReadingMode.vue";
import BackendCrossTrackerReport from "../backend-cross-tracker-report";
import ReadingCrossTrackerReport from "./reading-cross-tracker-report";
import * as rest_querier from "../api/rest-querier";
import type { Report, TrackerAndProject } from "../type";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { IS_USER_ADMIN, NOTIFY_FAULT, REPORT_ID, REPORT_STATE } from "../injection-symbols";

describe("ReadingMode", () => {
    let backend_cross_tracker_report: BackendCrossTrackerReport,
        reading_cross_tracker_report: ReadingCrossTrackerReport,
        is_user_admin: boolean,
        has_error: boolean,
        errorSpy: Mock;

    beforeEach(() => {
        backend_cross_tracker_report = new BackendCrossTrackerReport();
        reading_cross_tracker_report = new ReadingCrossTrackerReport();
        is_user_admin = true;
        has_error = false;
        errorSpy = vi.fn();
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof ReadingMode>> {
        return shallowMount(ReadingMode, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPORT_STATE.valueOf()]: ref("result-preview"),
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                    [REPORT_ID.valueOf()]: 875,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                },
            },
            props: {
                has_error,
                backend_cross_tracker_report,
                reading_cross_tracker_report,
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it("When I switch to the writing mode, then an event will be emitted", () => {
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeDefined();
            expect(wrapper.find("[data-test=tracker-list-reading-mode]").exists()).toBe(true);
        });

        it(`Given I am browsing as project member,
            when I try to switch to writing mode, nothing will happen`, () => {
            is_user_admin = false;
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeUndefined();
            expect(wrapper.find("[data-test=tracker-list-reading-mode]").exists()).toBe(true);
        });
    });

    describe("saveReport()", () => {
        it(`will update the backend report and emit a "saved" event`, async () => {
            const initBackend = vi.spyOn(backend_cross_tracker_report, "init");
            initBackend.mockImplementation(() => Promise.resolve());
            const duplicateBackend = vi.spyOn(backend_cross_tracker_report, "duplicateFromReport");
            const trackers: ReadonlyArray<TrackerAndProject> = [
                { tracker: { id: 36 }, project: { id: 180 } } as TrackerAndProject,
                { tracker: { id: 17 }, project: { id: 138 } } as TrackerAndProject,
            ];
            const expert_query = '@description != ""';
            const report = { trackers, expert_query, expert_mode: false } as Report;

            const updateReport = vi
                .spyOn(rest_querier, "updateReport")
                .mockReturnValue(okAsync(report));
            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(duplicateBackend).toHaveBeenCalledWith(reading_cross_tracker_report);
            expect(updateReport).toHaveBeenCalled();
            expect(initBackend).toHaveBeenCalledWith(trackers, expert_query, false);
            const emitted = wrapper.emitted("saved");
            expect(emitted).toBeDefined();
        });

        it("Given the report is in error, then nothing will happen", async () => {
            has_error = true;
            const updateReport = vi.spyOn(rest_querier, "updateReport");

            const wrapper = instantiateComponent();
            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", async () => {
            vi.spyOn(rest_querier, "updateReport").mockReturnValue(
                errAsync(Fault.fromMessage("Report not found")),
            );

            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isSaveReport()).toBe(true);
        });
    });

    describe("cancelReport()", () => {
        it(`when the report is unsaved and I click on "Cancel", then an event will be emitted`, async () => {
            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-cancel-report]").trigger("click");

            expect(wrapper.emitted("discard-unsaved-report")).toBeDefined();
        });
    });
});
