/*
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
import { nextTick } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import TrackerSelection from "./TrackerSelection.vue";
import * as rest_querier from "../../api/rest-querier";
import type { ProjectInfo, SelectedTracker, TrackerInfo } from "../../type";
import type { RetrieveProjects } from "../../domain/RetrieveProjects";
import { RetrieveProjectsStub } from "../../../tests/stubs/RetrieveProjectsStub";
import { ProjectInfoStub } from "../../../tests/stubs/ProjectInfoStub";
import { NOTIFY_FAULT, RETRIEVE_PROJECTS } from "../../injection-symbols";

vi.useFakeTimers();

describe("TrackerSelection", () => {
    let errorSpy: Mock,
        projects_retriever: RetrieveProjects,
        first_project: ProjectInfo,
        second_project: ProjectInfo;

    beforeEach(() => {
        errorSpy = vi.fn();
        first_project = ProjectInfoStub.withId(543);
        second_project = ProjectInfoStub.withId(544);
        projects_retriever = RetrieveProjectsStub.withProjects(first_project, second_project);
    });

    function getWrapper(
        selected_trackers: Array<SelectedTracker> = [],
    ): VueWrapper<InstanceType<typeof TrackerSelection>> {
        return shallowMount(TrackerSelection, {
            props: {
                selected_trackers,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [NOTIFY_FAULT.valueOf()]: errorSpy,
                    [RETRIEVE_PROJECTS.valueOf()]: projects_retriever,
                },
            },
        });
    }

    describe("mounted()", () => {
        beforeEach(() => {
            vi.spyOn(rest_querier, "getTrackersOfProject").mockReturnValue(okAsync([]));
        });

        it("on init, the projects will be loaded", () => {
            const getSortedProjectsIAmMemberOf = vi.spyOn(
                projects_retriever,
                "getSortedProjectsIAmMemberOf",
            );
            getWrapper();
            expect(getSortedProjectsIAmMemberOf).toHaveBeenCalled();
        });
    });

    describe("loadProjects()", () => {
        beforeEach(() => {
            vi.spyOn(rest_querier, "getTrackersOfProject").mockReturnValue(okAsync([]));
        });

        it("Displays an error when rest route fails", async () => {
            projects_retriever = RetrieveProjectsStub.withFault(Fault.fromMessage("Not Found"));

            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isProjectsRetrieval()).toBe(true);

            expect(wrapper.find("[data-test=tracker-loader]").classes()).not.toContain("fa-spin");
        });

        it("Displays the projects in selectbox", async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.selected_project).toStrictEqual(first_project);
            expect(wrapper.vm.projects).toStrictEqual([first_project, second_project]);
        });
    });

    describe("loadTrackers()", () => {
        it(`will show a loader and will disable the tracker options that are already selected`, async () => {
            const first_tracker = { id: 8, label: "coquettish" } as TrackerInfo;
            const second_tracker = { id: 26, label: "unfruitfully" } as TrackerInfo;
            const trackers = [first_tracker, second_tracker];
            vi.spyOn(rest_querier, "getTrackersOfProject").mockReturnValue(okAsync(trackers));

            const wrapper = getWrapper([{ tracker_id: 26 } as SelectedTracker]);
            wrapper.vm.selected_project = { id: 102 } as ProjectInfo;
            await nextTick(); // wait for watch
            expect(wrapper.find("[data-test=tracker-loader]").classes()).toContain("fa-spin");

            await vi.runOnlyPendingTimersAsync();
            expect(
                wrapper
                    .findAll("[data-test=cross-tracker-selector-tracker] > option")
                    .map((wrapper) => wrapper.attributes("disabled")),
            ).not.toHaveLength(0);
        });

        it("when there is a REST error, it will be displayed", async () => {
            vi.spyOn(rest_querier, "getTrackersOfProject").mockReturnValue(
                errAsync(Fault.fromMessage("Not Found")),
            );

            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][0].isTrackersRetrieval()).toBe(true);
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then an event will be emitted", async () => {
            const tracker = { id: 96, label: "simplus" } as TrackerInfo;
            const tracker_to_add = { id: 97, label: "acinus" } as TrackerInfo;
            vi.spyOn(rest_querier, "getTrackersOfProject").mockReturnValue(
                okAsync([tracker, tracker_to_add]),
            );

            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync(); // load projects
            wrapper.vm.selected_project = second_project;
            await vi.runOnlyPendingTimersAsync(); // load trackers
            wrapper.vm.tracker_to_add = tracker_to_add;
            await nextTick(); // wait for button to be enabled

            await wrapper.get("[data-test=cross-tracker-selector-tracker-button]").trigger("click");

            const emitted = wrapper.emitted("tracker-added");
            if (!emitted) {
                throw Error("Event has not been emitted");
            }
            expect(emitted[0][0]).toStrictEqual({
                selected_project: second_project,
                selected_tracker: tracker_to_add,
            });
            expect(wrapper.vm.tracker_to_add).toBeNull();
        });
    });
});
