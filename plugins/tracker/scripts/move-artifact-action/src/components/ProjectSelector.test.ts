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
import * as list_picker from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { useSelectorsStore } from "../stores/selectors";
import { PROJECT_ID } from "../injection-symbols";
import ProjectSelector from "./ProjectSelector.vue";

vi.mock("@tuleap/vue-strict-inject");

const current_project_id = 217;
const projects = [
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
    let createListPicker: SpyInstance, list_picker_instance: ListPicker;

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            if (key !== PROJECT_ID) {
                throw new Error(`Tried to inject ${key} while it was not mocked.`);
            }

            return current_project_id;
        });

        return shallowMount(ProjectSelector, {
            global: {
                ...getGlobalTestOptions({
                    initialState: {
                        selectors: {
                            projects,
                        },
                    },
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
    });

    it("should commit the current project id and load its trackers once created", () => {
        getWrapper();

        const selectors_store = useSelectorsStore();

        expect(selectors_store.saveSelectedProjectId).toHaveBeenCalledWith(current_project_id);
        expect(selectors_store.loadTrackerList).toHaveBeenCalledWith(current_project_id);
    });

    it("should create a list-picker on its <select> input once mounted", () => {
        getWrapper();

        expect(createListPicker).toHaveBeenCalledTimes(1);
    });

    it("the <select> should display the projects", () => {
        const wrapper = getWrapper();
        const select = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-project-selector]",
        ).element;

        expect(select.options).toHaveLength(projects.length);

        const select_options = Array.from(select.options);

        expect(select_options[0].value).toBe(String(projects[0].id));
        expect(select_options[0].label).toBe(projects[0].label);

        expect(select_options[1].value).toBe(String(projects[1].id));
        expect(select_options[1].label).toBe(projects[1].label);
    });

    it("When a project is selected, then a loadTrackerList event should be dispatched with the selected project's id", () => {
        const wrapper = getWrapper();

        const select_wrapper = wrapper.find<HTMLSelectElement>(
            "[data-test=move-artifact-project-selector]",
        );

        select_wrapper.element.selectedIndex = 1;
        select_wrapper.trigger("change");

        expect(useSelectorsStore().loadTrackerList).toHaveBeenCalledWith(projects[1].id);
    });

    it("When the component is about to be destroyed, then the list picker instance should be destroyed.", () => {
        const wrapper = getWrapper();

        wrapper.unmount();

        expect(list_picker_instance.destroy).toHaveBeenCalled();
    });
});
