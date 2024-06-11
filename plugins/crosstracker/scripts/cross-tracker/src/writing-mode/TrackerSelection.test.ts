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

import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import TrackerSelection from "./TrackerSelection.vue";
import * as rest_querier from "../api/rest-querier";
import type { ProjectInfo, SelectedTracker, State, TrackerInfo } from "../type";
import type { RetrieveProjects } from "./RetrieveProjects";
import { RetrieveProjectsStub } from "../../tests/stubs/RetrieveProjectsStub";
import { ProjectInfoStub } from "../../tests/stubs/ProjectInfoStub";

jest.useFakeTimers();

describe("TrackerSelection", () => {
    let errorSpy: jest.Mock,
        projects_retriever: RetrieveProjects,
        first_project: ProjectInfo,
        second_project: ProjectInfo;

    beforeEach(() => {
        errorSpy = jest.fn();
        first_project = ProjectInfoStub.withId(543);
        second_project = ProjectInfoStub.withId(544);
        projects_retriever = RetrieveProjectsStub.withProjects(first_project, second_project);
    });

    function getWrapper(
        selectedTrackers: Array<SelectedTracker> = [],
    ): VueWrapper<InstanceType<typeof TrackerSelection>> {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue(projects_retriever);
        const store_options = {
            state: { is_user_admin: true } as State,
            mutations: {
                setErrorMessage: errorSpy,
            },
        };

        return shallowMount(TrackerSelection, {
            props: {
                selectedTrackers,
            },
            global: { ...getGlobalTestOptions(store_options) },
        });
    }

    describe("mounted()", () => {
        it("on init, the projects will be loaded", () => {
            const getSortedProjectsIAmMemberOf = jest.spyOn(
                projects_retriever,
                "getSortedProjectsIAmMemberOf",
            );
            getWrapper();
            expect(getSortedProjectsIAmMemberOf).toHaveBeenCalled();
        });
    });

    describe("loadProjects()", () => {
        beforeEach(() => {
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue([]);
        });

        it("Displays an error when rest route fails", async () => {
            const error_message = "Not Found";
            projects_retriever = RetrieveProjectsStub.withFault(Fault.fromMessage(error_message));

            const wrapper = getWrapper();
            await jest.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalled();
            expect(errorSpy.mock.calls[0][1]).toContain(error_message);

            expect(wrapper.find("[data-test=tracker-loader]").classes()).not.toContain("fa-spin");
        });

        it("Displays the projects in selectbox", async () => {
            const wrapper = getWrapper();
            await jest.runOnlyPendingTimersAsync();

            expect(wrapper.vm.selected_project).toStrictEqual(first_project);
            expect(wrapper.vm.projects).toStrictEqual([first_project, second_project]);
        });
    });

    describe("loadTrackers()", () => {
        it("when I load trackers, the loader will be shown and the trackers options will be disabled if already selected", async () => {
            const first_tracker = { id: 8, label: "coquettish" } as TrackerInfo;
            const second_tracker = { id: 26, label: "unfruitfully" } as TrackerInfo;
            const trackers = [first_tracker, second_tracker];
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue(trackers);

            const wrapper = getWrapper([{ tracker_id: 26 } as SelectedTracker]);
            await jest.runOnlyPendingTimersAsync();

            expect(wrapper.vm.trackers).toStrictEqual(trackers);
        });

        it("when there is a REST error, it will be displayed", async () => {
            jest.spyOn(rest_querier, "getTrackersOfProject").mockRejectedValue([]);

            getWrapper();
            await jest.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalledWith(expect.any(Object), expect.any(String));
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then an event will be emitted", async () => {
            const tracker = { id: 96, label: "simplus" } as TrackerInfo;
            const tracker_to_add = { id: 97, label: "acinus" } as TrackerInfo;
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue([
                tracker,
                tracker_to_add,
            ]);

            const wrapper = getWrapper();
            await jest.runOnlyPendingTimersAsync();

            wrapper.vm.selected_project = second_project;
            wrapper.vm.tracker_to_add = tracker_to_add;

            wrapper
                .find("[data-test=cross-tracker-selector-tracker-button]")
                .element.removeAttribute("disabled");
            await wrapper.get("[data-test=cross-tracker-selector-tracker-button]").trigger("click");

            const emitted = wrapper.emitted("tracker-added");
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }
            expect(emitted[0][0]).toStrictEqual({
                selected_project: second_project,
                selected_tracker: tracker_to_add,
            });
            expect(wrapper.vm.tracker_to_add).toBeNull();
        });
    });
});
