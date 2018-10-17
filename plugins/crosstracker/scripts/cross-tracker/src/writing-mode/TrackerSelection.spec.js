/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import TrackerSelection from "./TrackerSelection.vue";
import { rewire$getSortedProjectsIAmMemberOf, restore } from "./projects-cache.js";
import { rewire$getTrackersOfProject, restore as restoreRest } from "../rest-querier.js";

describe("TrackerSelection", () => {
    let Selection, errorDisplayer, selectedTrackers;

    beforeEach(() => {
        Selection = Vue.extend(TrackerSelection);
        selectedTrackers = [];
    });

    function instantiateComponent() {
        const vm = new Selection({
            propsData: {
                selectedTrackers
            }
        });
        vm.$mount();

        return vm;
    }

    describe("mounted()", () => {
        it("on init, the projects will be loaded", () => {
            spyOn(TrackerSelection.methods, "loadProjects");

            instantiateComponent();

            expect(TrackerSelection.methods.loadProjects).toHaveBeenCalled();
        });
    });

    describe("loadProjects()", () => {
        let getProjects;

        beforeEach(() => {
            getProjects = jasmine.createSpy("getSortedProjectsIAmMemberOf");
            rewire$getSortedProjectsIAmMemberOf(getProjects);
            spyOn(TrackerSelection.methods, "loadTrackers");
        });

        afterEach(() => {
            restore();
        });

        it("when I load projects, the loader will be shown and the first project fetched will be set as selected", async () => {
            const first_project = { id: 543, label: "unheroically" };
            const second_project = { id: 554, label: "cycler" };
            getProjects.and.returnValue(Promise.resolve([first_project, second_project]));
            const vm = instantiateComponent();

            const promise = vm.loadProjects();
            expect(vm.is_loader_shown).toBe(true);
            expect(vm.is_project_select_disabled).toBe(true);

            await promise;
            expect(vm.projects).toEqual([first_project, second_project]);
            expect(vm.selected_project).toBe(first_project);
            expect(vm.is_loader_shown).toBe(false);
        });

        it("when there is a REST error, it will be displayed", () => {
            getProjects.and.returnValue(Promise.reject(500));
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.loadProjects().then(
                () => {
                    fail();
                },
                () => {
                    expect(vm.$emit).toHaveBeenCalledWith("error", jasmine.any(String));
                    expect(errorDisplayer.displayError).toHaveBeenCalled();
                    expect(vm.is_loader_shown).toBe(false);
                }
            );
        });
    });

    describe("loadTrackers()", () => {
        let getTrackers;

        beforeEach(() => {
            getTrackers = jasmine.createSpy("getTrackersOfProject");
            rewire$getTrackersOfProject(getTrackers);
        });

        afterEach(() => {
            restoreRest();
        });

        it("when I load trackers, the loader will be shown and the trackers options will be disabled if already selected", async () => {
            const first_tracker = { id: 8, label: "coquettish" };
            const second_tracker = { id: 26, label: "unfruitfully" };
            const trackers = [first_tracker, second_tracker];
            selectedTrackers = [{ tracker_id: 26 }];
            getTrackers.and.returnValue(Promise.resolve(trackers));
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
                { id: 26, label: "unfruitfully", disabled: true }
            ]);
        });

        it("when there is a REST error, it will be displayed", () => {
            getTrackers.and.returnValue(Promise.reject(500));
            const project_id = 34;
            const vm = instantiateComponent();
            spyOn(vm, "$emit");

            vm.loadTrackers(project_id).then(
                () => {
                    fail();
                },
                () => {
                    expect(vm.$emit).toHaveBeenCalledWith("error", jasmine.any(String));
                    expect(vm.is_loader_shown).toBe(false);
                }
            );
        });
    });

    describe("addTrackerToSelection()", () => {
        it("when I add a tracker, then an event will be emitted", () => {
            spyOn(TrackerSelection.methods, "loadTrackers");
            const vm = instantiateComponent();
            const selected_project = { id: 972, label: "unmortised" };
            const selected_tracker = { id: 97, label: "acinus" };
            vm.selected_project = selected_project;
            vm.selected_tracker = selected_tracker;
            spyOn(vm, "$emit");

            vm.addTrackerToSelection();

            expect(vm.$emit).toHaveBeenCalledWith("trackerAdded", {
                selected_project,
                selected_tracker
            });
            expect(vm.selected_tracker).toBe(null);
        });
    });
});
