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
import type { Wrapper } from "@vue/test-utils";
import { createCrossTrackerLocalVue } from "../helpers/local-vue-for-test";
import TrackerSelection from "./TrackerSelection.vue";
import * as project_cache from "./projects-cache";
import * as rest_querier from "../api/rest-querier";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ProjectInfo, SelectedTracker, State, TrackerInfo } from "../type";

describe("TrackerSelection", () => {
    let store = {
        commit: jest.fn(),
    };

    async function instantiateComponent(
        selectedTrackers: Array<SelectedTracker> = [],
    ): Promise<Wrapper<TrackerSelection>> {
        const store_options = { state: { is_user_admin: true } as State, commit: jest.fn() };
        store = createStoreMock(store_options);

        return shallowMount(TrackerSelection, {
            localVue: await createCrossTrackerLocalVue(),
            propsData: {
                selectedTrackers,
            },
            mocks: { $store: store },
        });
    }

    describe("mounted()", () => {
        it("on init, the projects will be loaded", async () => {
            const loadProjects = jest
                .spyOn(rest_querier, "getSortedProjectsIAmMemberOf")
                .mockImplementation(() => Promise.resolve([]));

            await instantiateComponent();

            expect(loadProjects).toHaveBeenCalled();
        });
    });

    describe("loadProjects()", () => {
        beforeEach(() => {
            jest.spyOn(rest_querier, "getTrackersOfProject").mockImplementation(() =>
                Promise.resolve([]),
            );
        });

        it("Displays an error when rest route fail", async () => {
            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockRejectedValue([]);

            const wrapper = await instantiateComponent();
            await wrapper.vm.$nextTick(); // for the promise
            await wrapper.vm.$nextTick(); // for the finally

            expect(store.commit).toHaveBeenCalledWith("setErrorMessage", expect.anything());

            expect(wrapper.find("[data-test=tracker-loader]").exists()).toBe(false);
        });

        it("Displays the projects in selectbox", async () => {
            const first_project = { id: 543, label: "unheroically" } as ProjectInfo;
            const second_project = { id: 544, label: "cycler" } as ProjectInfo;

            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockResolvedValue([
                first_project,
                second_project,
            ]);

            const wrapper = await instantiateComponent();
            await wrapper.vm.$nextTick();

            expect(wrapper.element).toMatchSnapshot();
            expect(wrapper.vm.$data.selected_project).toBe(first_project);
            expect(wrapper.vm.$data.projects).toStrictEqual([first_project, second_project]);
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
            const wrapper = await instantiateComponent([{ tracker_id: 26 } as SelectedTracker]);

            await wrapper.vm.$nextTick(); // for the promise of project
            await wrapper.vm.$nextTick(); // for the promise of tracker
            await wrapper.vm.$nextTick(); // for the finally

            expect(wrapper.vm.$data.trackers).toStrictEqual(trackers);
        });

        it("when there is a REST error, it will be displayed", async () => {
            const project = { id: 543, label: "unheroically" } as ProjectInfo;
            jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf").mockResolvedValue([project]);
            jest.spyOn(rest_querier, "getTrackersOfProject").mockRejectedValue([]);

            const wrapper = await instantiateComponent();
            await wrapper.vm.$nextTick(); // for the promise of project
            await wrapper.vm.$nextTick(); // for the promise of tracker
            await wrapper.vm.$nextTick(); // for the finally

            expect(store.commit).toHaveBeenCalledWith("setErrorMessage", expect.anything());
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
            const selected_tracker = { id: 97, label: "acinus" } as TrackerInfo;
            jest.spyOn(rest_querier, "getTrackersOfProject").mockResolvedValue([
                tracker,
                selected_tracker,
            ]);

            const wrapper = await instantiateComponent();

            await wrapper.vm.$nextTick(); // for the promise of project
            await wrapper.vm.$nextTick(); // for the promise of tracker
            await wrapper.vm.$nextTick(); // for the finally

            wrapper.vm.$data.selected_project = selected_project;
            wrapper.vm.$data.selected_tracker = selected_tracker;

            wrapper
                .find("[data-test=cross-tracker-selector-tracker-button]")
                .element.removeAttribute("disabled");
            wrapper.get("[data-test=cross-tracker-selector-tracker-button]").trigger("click");

            const emitted = wrapper.emitted()["tracker-added"];
            if (!emitted) {
                throw new Error("Event has not been emitted");
            }
            expect(emitted[0][0]).toStrictEqual({
                selected_project,
                selected_tracker,
            });
            expect(wrapper.vm.$data.selected_tracker).toBeNull();
        });
    });
});
