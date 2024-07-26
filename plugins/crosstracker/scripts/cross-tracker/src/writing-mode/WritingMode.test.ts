/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import WritingMode from "./WritingMode.vue";
import {
    default as WritingCrossTrackerReport,
    TooManyTrackersSelectedError,
} from "./writing-cross-tracker-report";
import type { ProjectInfo, State, TrackerInfo, TrackerToUpdate } from "../type";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";
import TrackerSelection from "./TrackerSelection.vue";
import { CLEAR_FEEDBACKS, NOTIFY_FAULT } from "../injection-symbols";

describe("WritingMode", () => {
    let resetSpy: Mock, errorSpy: Mock;

    beforeEach(() => {
        resetSpy = vi.fn();
        errorSpy = vi.fn();
    });

    function getWrapper(
        writing_cross_tracker_report: WritingCrossTrackerReport,
    ): VueWrapper<InstanceType<typeof WritingMode>> {
        const store_options = {
            state: { is_user_admin: true } as State,
        };

        return shallowMount(WritingMode, {
            props: {
                writing_cross_tracker_report,
            },
            global: {
                ...getGlobalTestOptions(store_options),
                provide: {
                    [CLEAR_FEEDBACKS.valueOf()]: resetSpy,
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                },
            },
        });
    }

    describe("mounted()", () => {
        it("on init, the selected trackers will be formatted from the writing report", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            writing_cross_tracker_report.addTracker(
                { id: 804, label: "fanatical" } as ProjectInfo,
                { id: 29, label: "charry" } as TrackerInfo,
            );
            writing_cross_tracker_report.addTracker(
                { id: 146, label: "surly" } as ProjectInfo,
                { id: 51, label: "monodynamism" } as TrackerInfo,
            );

            const wrapper = getWrapper(writing_cross_tracker_report);

            expect(wrapper.vm.selected_trackers).toStrictEqual([
                {
                    tracker_id: 29,
                    tracker_label: "charry",
                    project_label: "fanatical",
                },
                {
                    tracker_id: 51,
                    tracker_label: "monodynamism",
                    project_label: "surly",
                },
            ]);
        });
    });

    describe("cancel()", () => {
        it("when I hit cancel, then an event will be emitted to switch the widget to reading mode in saved state", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            const wrapper = getWrapper(writing_cross_tracker_report);

            wrapper.find("[data-test=writing-mode-cancel-button]").trigger("click");
            const emitted = wrapper.emitted("switch-to-reading-mode");
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }

            expect(emitted[0][0]).toStrictEqual({ saved_state: true });
        });
    });

    describe("search()", () => {
        it("when I hit search, then an event will be emitted to switch the widget to reading mode in unsaved state", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            const wrapper = getWrapper(writing_cross_tracker_report);

            wrapper.find("[data-test=search-report-button]").trigger("click");
            const emitted = wrapper.emitted("switch-to-reading-mode");
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }

            expect(emitted[0][0]).toStrictEqual({ saved_state: false });
        });
    });

    describe("removeTrackerFromSelection()", () => {
        it("when I remove a tracker, then the writing report will be updated and the errors hidden", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            writing_cross_tracker_report.addTracker(
                { id: 172, label: "undiuretic" } as ProjectInfo,
                { id: 61, label: "Dipneumona" } as TrackerInfo,
            );
            writing_cross_tracker_report.addTracker(
                { id: 288, label: "defectless" } as ProjectInfo,
                { id: 46, label: "knothorn" } as TrackerInfo,
            );
            vi.spyOn(writing_cross_tracker_report, "removeTracker");
            const wrapper = getWrapper(writing_cross_tracker_report);

            wrapper
                .findComponent(TrackerListWritingMode)
                .vm.$emit("tracker-removed", { tracker_id: 46 } as TrackerToUpdate);

            expect(writing_cross_tracker_report.removeTracker).toHaveBeenCalledWith(46);
            expect(resetSpy).toHaveBeenCalled();
            expect(wrapper.vm.selected_trackers).toStrictEqual([
                {
                    tracker_id: 61,
                    tracker_label: "Dipneumona",
                    project_label: "undiuretic",
                },
            ]);
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then the writing report will be updated", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            vi.spyOn(writing_cross_tracker_report, "addTracker");
            const wrapper = getWrapper(writing_cross_tracker_report);
            const selected_project = { id: 656, label: "ergatogyne" } as ProjectInfo;
            const selected_tracker = { id: 53, label: "observingly" } as TrackerInfo;

            wrapper.findComponent(TrackerSelection).vm.$emit("tracker-added", {
                selected_project,
                selected_tracker,
            });

            expect(writing_cross_tracker_report.addTracker).toHaveBeenCalledWith(
                selected_project,
                selected_tracker,
            );
            expect(wrapper.vm.selected_trackers).toStrictEqual([
                {
                    tracker_id: 53,
                    tracker_label: "observingly",
                    project_label: "ergatogyne",
                },
            ]);
        });

        it("Given I had already added 25 trackers, when I try to add another, then an error will be shown", () => {
            const writing_cross_tracker_report = new WritingCrossTrackerReport();
            vi.spyOn(writing_cross_tracker_report, "addTracker").mockImplementation(() => {
                throw new TooManyTrackersSelectedError();
            });
            const wrapper = getWrapper(writing_cross_tracker_report);
            const selected_project = { id: 656, label: "ergatogyne" } as ProjectInfo;
            const selected_tracker = { id: 53, label: "observingly" } as TrackerInfo;

            wrapper.findComponent(TrackerSelection).vm.$emit("tracker-added", {
                selected_project,
                selected_tracker,
            });

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isMaxTrackersSelected()).toBe(true);
        });
    });
});
