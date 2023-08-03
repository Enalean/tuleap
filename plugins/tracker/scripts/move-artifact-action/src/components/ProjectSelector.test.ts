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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as list_picker from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker/src";
import { createMoveModalLocalVue } from "../../tests/local-vue-for-tests";
import { setFromTracker } from "../from-tracker-presenter";

import ProjectSelector from "./ProjectSelector.vue";

const getWrapper = async (store: Store): Promise<Wrapper<ProjectSelector>> =>
    shallowMount(ProjectSelector, {
        localVue: await createMoveModalLocalVue(),
        mocks: {
            $store: store,
        },
    });

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

const current_project_id = 217;

describe("ProjectSelector", () => {
    let store: Store, createListPicker: jest.SpyInstance, list_picker_instance: ListPicker;

    beforeEach(() => {
        setFromTracker(10, "Tasks", "red-wine", 12, current_project_id);

        list_picker_instance = {
            destroy: jest.fn(),
        };
        createListPicker = jest
            .spyOn(list_picker, "createListPicker")
            .mockReturnValue(list_picker_instance);

        store = createStoreMock({
            getters: {
                sorted_projects,
            },
        });
    });

    it("should commit the current project id and load its trackers once created", async () => {
        await getWrapper(store);

        expect(store.commit).toHaveBeenCalledWith("saveSelectedProjectId", current_project_id);
        expect(store.dispatch).toHaveBeenCalledWith("loadTrackerList", current_project_id);
    });

    it("should create a list-picker on its <select> input once mounted", async () => {
        await getWrapper(store);

        expect(createListPicker).toHaveBeenCalledTimes(1);
    });

    it("the <select> should display the projects", async () => {
        const wrapper = await getWrapper(store);
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

    it("When a project is selected, then a loadTrackerList event should be dispatched with the selected project's id", async () => {
        const wrapper = await getWrapper(store);

        const select_wrapper = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-project-selector]"
        );

        select_wrapper.element.selectedIndex = 1;
        select_wrapper.trigger("change");

        expect(store.dispatch).toHaveBeenCalledWith("loadTrackerList", sorted_projects[1].id);
    });

    it("When the component is about to be destroyed, then the list picker instance should be destroyed.", async () => {
        const wrapper = await getWrapper(store);

        wrapper.destroy();

        expect(list_picker_instance.destroy).toHaveBeenCalled();
    });
});
