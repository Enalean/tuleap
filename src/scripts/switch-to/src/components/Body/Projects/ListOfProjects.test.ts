/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import ListOfProjects from "./ListOfProjects.vue";
import { createTestingPinia } from "@pinia/testing";
import type { Project } from "../../../type";
import ProjectsEmptyState from "./ProjectsEmptyState.vue";
import ProjectLink from "./ProjectLink.vue";
import type { State } from "../../../stores/type";
import { defineStore } from "pinia";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ListOfProjects", () => {
    it("Displays empty state if no projects", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    projects: [] as Project[],
                }) as State,
            getters: {
                filtered_projects: (): Project[] => [],
                is_in_search_mode: (): boolean => false,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfProjects, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.findComponent(ProjectsEmptyState).exists()).toBe(true);
    });

    it("Display list of filtered projects", () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    projects: [
                        { project_uri: "/a" } as Project,
                        { project_uri: "/b" } as Project,
                        { project_uri: "/c" } as Project,
                    ],
                }) as State,
            getters: {
                filtered_projects: (): Project[] => [
                    { project_uri: "/a" } as Project,
                    { project_uri: "/b" } as Project,
                ],
                is_in_search_mode: (): boolean => true,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfProjects, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.findAllComponents(ProjectLink)).toHaveLength(2);
        expect(wrapper.findComponent(ProjectsEmptyState).exists()).toBe(false);
    });

    it(`Given user is searching for a term
        When there is no matching projects
        Then we should not display anything`, () => {
        const useSwitchToStore = defineStore("root", {
            state: (): State =>
                ({
                    projects: [
                        { project_uri: "/a" } as Project,
                        { project_uri: "/b" } as Project,
                        { project_uri: "/c" } as Project,
                    ],
                }) as State,
            getters: {
                filtered_projects: (): Project[] => [],
                is_in_search_mode: (): boolean => true,
            },
        });

        const pinia = createTestingPinia();
        useSwitchToStore(pinia);

        const wrapper = shallowMount(ListOfProjects, {
            global: getGlobalTestOptions(pinia),
        });

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });
});
