/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { vi, describe, beforeEach, it, expect } from "vitest";
import type { SpyInstance } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ActionTree, MutationTree } from "vuex";
import * as list_picker from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptionsWithMockedStore } from "../../tests/global-options-for-tests";
import { PROJECT_ID } from "../injection-symbols";
import ProjectSelector from "./ProjectSelector.vue";
import type { RootState } from "../store/types";

vi.mock("@tuleap/vue-strict-inject");

const current_project_id = 217;
const sorted_projects = [
    {
        id: 101,
        label: "Project 1",
    },
    {
        id: 102,
        label: "Project 2",
    },
];

describe("ProjectSelector", () => {
    let createListPicker: SpyInstance,
        list_picker_instance: ListPicker,
        loadTrackerList: SpyInstance,
        saveSelectedProjectId: SpyInstance;

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            if (key !== PROJECT_ID) {
                throw new Error(`Tried to inject ${key} while it was not mocked.`);
            }

            return current_project_id;
        });

        return shallowMount(ProjectSelector, {
            global: {
                ...getGlobalTestOptionsWithMockedStore({
                    state: {
                        projects: sorted_projects,
                    } as RootState,
                    actions: { loadTrackerList } as unknown as ActionTree<RootState, RootState>,
                    mutations: { saveSelectedProjectId } as unknown as MutationTree<RootState>,
                }),
            },
        });
    };

    beforeEach(() => {
        list_picker_instance = {
            destroy: vi.fn(),
        };

        createListPicker = vi
            .spyOn(list_picker, "createListPicker")
            .mockReturnValue(list_picker_instance);

        loadTrackerList = vi.fn();
        saveSelectedProjectId = vi.fn();
    });

    it("should commit the current project id and load its trackers once created", () => {
        getWrapper();

        expect(saveSelectedProjectId).toHaveBeenCalledWith(expect.any(Object), current_project_id);
        expect(loadTrackerList).toHaveBeenCalledWith(expect.any(Object), current_project_id);
    });

    it("should create a list-picker on its <select> input once mounted", () => {
        getWrapper();

        expect(createListPicker).toHaveBeenCalledTimes(1);
    });

    it("the <select> should display the projects", () => {
        const wrapper = getWrapper();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-project-selector]"
        ).element;

        expect(select.options).toHaveLength(sorted_projects.length);

        const select_options = Array.from(select.options);

        expect(select_options[0].value).toBe(String(sorted_projects[0].id));
        expect(select_options[0].label).toBe(sorted_projects[0].label);

        expect(select_options[1].value).toBe(String(sorted_projects[1].id));
        expect(select_options[1].label).toBe(sorted_projects[1].label);
    });

    it("When a project is selected, then a loadTrackerList event should be dispatched with the selected project's id", () => {
        const wrapper = getWrapper();

        const select_wrapper = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-project-selector]"
        );

        select_wrapper.element.selectedIndex = 1;
        select_wrapper.trigger("change");

        expect(loadTrackerList).toHaveBeenCalledWith(expect.any(Object), sorted_projects[1].id);
    });

    it("When the component is about to be destroyed, then the list picker instance should be destroyed.", () => {
        const wrapper = getWrapper();

        wrapper.unmount();

        expect(list_picker_instance.destroy).toHaveBeenCalled();
    });
});
