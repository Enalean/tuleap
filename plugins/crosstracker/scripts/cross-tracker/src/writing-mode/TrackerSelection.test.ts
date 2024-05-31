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
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import TrackerSelection from "./TrackerSelection.vue";
import * as project_cache from "./projects-cache";
import * as rest_querier from "../api/rest-querier";
import type { ProjectInfo, SelectedTracker, State, TrackerInfo } from "../type";

jest.useFakeTimers();

describe("TrackerSelection", () => {
    let errorSpy: jest.Mock;

    beforeEach(() => {
        errorSpy = jest.fn();
    });

    function instantiateComponent(
        selectedTrackers: Array<SelectedTracker> = [],
    ): VueWrapper<InstanceType<typeof TrackerSelection>> {
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
            const loadProjects = jest
                .spyOn(rest_querier, "getSortedProjectsIAmMemberOf")
                .mockResolvedValue([]);

            instantiateComponent();

            expect(loadProjects).toHaveBeenCalled();
        });
    });

    describe("loadProjects()", () => {
        beforeEach(() => {
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue([]);
        });

        it("Displays an error when rest route fail", async () => {
            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockRejectedValue([]);

            const wrapper = instantiateComponent();
            await jest.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalledWith(expect.any(Object), expect.any(String));

            expect(wrapper.find("[data-test=tracker-loader]").attributes("class")).not.toContain(
                "fa-spin",
            );
        });

        it("Displays the projects in selectbox", async () => {
            const first_project = { id: 543, label: "unheroically" } as ProjectInfo;
            const second_project = { id: 544, label: "cycler" } as ProjectInfo;

            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockResolvedValue([
                first_project,
                second_project,
            ]);

            const wrapper = instantiateComponent();
            await jest.runOnlyPendingTimersAsync();

            expect(wrapper.element).toMatchSnapshot();
            expect(wrapper.vm.selected_project).toStrictEqual(first_project);
            expect(wrapper.vm.projects).toStrictEqual([first_project, second_project]);
        });
    });

    describe("loadTrackers()", () => {
        it("when I load trackers, the loader will be shown and the trackers options will be disabled if already selected", async () => {
            const project = { id: 543, label: "unheroically" } as ProjectInfo;

            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockResolvedValue([project]);

            const first_tracker = { id: 8, label: "coquettish" } as TrackerInfo;
            const second_tracker = { id: 26, label: "unfruitfully" } as TrackerInfo;
            const trackers = [first_tracker, second_tracker];
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue(trackers);

            const wrapper = instantiateComponent([{ tracker_id: 26 } as SelectedTracker]);
            await jest.runOnlyPendingTimersAsync();

            expect(wrapper.vm.trackers).toStrictEqual(trackers);
        });

        it("when there is a REST error, it will be displayed", async () => {
            const project = { id: 543, label: "unheroically" } as ProjectInfo;
            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockResolvedValue([project]);
            jest.spyOn(rest_querier, "getTrackersOfProject").mockRejectedValue([]);

            instantiateComponent();
            await jest.runOnlyPendingTimersAsync();

            expect(errorSpy).toHaveBeenCalledWith(expect.any(Object), expect.any(String));
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then an event will be emitted", async () => {
            const project = { id: 972, label: "unheroically" } as ProjectInfo;
            const selected_project = { id: 543, label: "unmortised" } as ProjectInfo;
            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockResolvedValue([
                project,
                selected_project,
            ]);

            const tracker = { id: 96, label: "simplus" } as TrackerInfo;
            const tracker_to_add = { id: 97, label: "acinus" } as TrackerInfo;
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue([
                tracker,
                tracker_to_add,
            ]);

            const wrapper = instantiateComponent();
            await jest.runOnlyPendingTimersAsync();

            wrapper.vm.selected_project = selected_project;
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
                selected_project,
                selected_tracker: tracker_to_add,
            });
            expect(wrapper.vm.tracker_to_add).toBeNull();
        });
    });
});
