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

import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import { mockFetchError } from "../../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper.js";
import { createStore } from "../store/index.js";
import TrackerSelection from "./TrackerSelection.vue";
import * as project_cache from "./projects-cache.js";
import * as rest_querier from "../api/rest-querier.js";

describe("TrackerSelection", () => {
    let Selection, selectedTrackers;

    beforeEach(() => {
        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });
        Selection = Vue.extend(TrackerSelection);
        selectedTrackers = [];
    });

    function instantiateComponent() {
        const vm = new Selection({
            store: createStore(),
            propsData: {
                selectedTrackers,
            },
        });
        vm.$mount();
        jest.spyOn(vm.$store, "commit").mockImplementation(() => {});

        return vm;
    }

    describe("mounted()", () => {
        it("on init, the projects will be loaded", () => {
            jest.spyOn(TrackerSelection.methods, "loadProjects").mockImplementation(() => {});

            instantiateComponent();

            expect(TrackerSelection.methods.loadProjects).toHaveBeenCalled();
        });
    });

    describe("loadProjects()", () => {
        let getProjects;

        beforeEach(() => {
            getProjects = jest.spyOn(project_cache, "getSortedProjectsIAmMemberOf");
            jest.spyOn(TrackerSelection.methods, "loadTrackers").mockImplementation(() => {});
        });

        it("when I load projects, the loader will be shown and the first project fetched will be set as selected", async () => {
            const first_project = { id: 543, label: "unheroically" };
            const second_project = { id: 554, label: "cycler" };
            getProjects.mockImplementation(() => Promise.resolve([first_project, second_project]));
            const vm = instantiateComponent();

            const promise = vm.loadProjects();
            expect(vm.is_loader_shown).toBe(true);
            expect(vm.is_project_select_disabled).toBe(true);

            await promise;
            expect(vm.projects).toEqual([first_project, second_project]);
            expect(vm.selected_project).toBe(first_project);
            expect(vm.is_loader_shown).toBe(false);
        });

        it("when there is a REST error, then it will be displayed", () => {
            mockFetchError(getProjects, { status: 500 });
            const vm = instantiateComponent();

            return vm.loadProjects().then(() => {
                expect(vm.$store.commit).toHaveBeenCalledWith(
                    "setErrorMessage",
                    expect.any(String)
                );
                expect(vm.is_loader_shown).toBe(false);
            });
        });
    });

    describe("loadTrackers()", () => {
        let getTrackers, vm;

        beforeEach(() => {
            getTrackers = jest.spyOn(rest_querier, "getTrackersOfProject");
            vm = instantiateComponent();
        });

        it("when I load trackers, the loader will be shown and the trackers options will be disabled if already selected", async () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockImplementation(() =>
                Promise.resolve([{ id: 102 }])
            );
            const first_tracker = { id: 8, label: "coquettish" };
            const second_tracker = { id: 26, label: "unfruitfully" };
            const trackers = [first_tracker, second_tracker];
            selectedTrackers = [{ tracker_id: 26 }];
            getTrackers.mockImplementation(() => Promise.resolve(trackers));
            const project_id = 20;
            const vm = instantiateComponent();

            const promise = vm.loadTrackers(project_id);
            expect(vm.is_loader_shown).toBe(true);
            expect(vm.is_tracker_select_disabled).toBe(true);

            await promise;
            expect(vm.is_loader_shown).toBe(false);
            expect(vm.trackers).toEqual(trackers);
            expect(vm.tracker_options).toEqual([
                { id: 8, label: "coquettish", disabled: false },
                { id: 26, label: "unfruitfully", disabled: true },
            ]);
        });

        it("when there is a REST error, it will be displayed", () => {
            const project_id = 34;
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockImplementation(() =>
                Promise.resolve([{ id: project_id }])
            );
            mockFetchError(getTrackers, { status: 500 });

            return vm.loadTrackers(project_id).then(() => {
                expect(vm.$store.commit).toHaveBeenCalledWith(
                    "setErrorMessage",
                    expect.any(String)
                );
                expect(vm.is_loader_shown).toBe(false);
            });
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then an event will be emitted", () => {
            jest.spyOn(rest_querier, "getSortedProjectsIAmMemberOf").mockImplementation(() =>
                Promise.resolve([{ id: 102 }])
            );
            jest.spyOn(TrackerSelection.methods, "loadTrackers").mockImplementation(() => {});
            const vm = instantiateComponent();
            const selected_project = { id: 972, label: "unmortised" };
            const selected_tracker = { id: 97, label: "acinus" };
            vm.selected_project = selected_project;
            vm.selected_tracker = selected_tracker;
            jest.spyOn(vm, "$emit").mockImplementation(() => {});

            vm.addTrackerToSelection();

            expect(vm.$emit).toHaveBeenCalledWith("trackerAdded", {
                selected_project,
                selected_tracker,
            });
            expect(vm.selected_tracker).toBe(null);
        });
    });
});
