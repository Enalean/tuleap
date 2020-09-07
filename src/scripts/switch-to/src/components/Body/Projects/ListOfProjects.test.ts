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
import { createSwitchToLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { State } from "../../../store/type";
import { Project } from "../../../type";
import ProjectsEmptyState from "./ProjectsEmptyState.vue";
import ProjectLink from "./ProjectLink.vue";
import TroveCatLink from "../TroveCatLink.vue";

describe("ListOfProjects", () => {
    it("Displays empty state if no projects", async () => {
        const wrapper = shallowMount(ListOfProjects, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        projects: [] as Project[],
                    } as State,
                    getters: {
                        filtered_projects: [] as Project[],
                    },
                }),
            },
        });

        expect(wrapper.findComponent(ProjectsEmptyState).exists()).toBe(true);
    });

    it("Display list of filtered projects", async () => {
        const wrapper = shallowMount(ListOfProjects, {
            localVue: await createSwitchToLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        projects: [
                            { project_uri: "/a" } as Project,
                            { project_uri: "/b" } as Project,
                            { project_uri: "/c" } as Project,
                        ],
                    } as State,
                    getters: {
                        filtered_projects: [
                            { project_uri: "/a" } as Project,
                            { project_uri: "/b" } as Project,
                        ],
                    },
                }),
            },
        });

        expect(wrapper.findAllComponents(ProjectLink).length).toBe(2);
        expect(wrapper.findComponent(ProjectsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(TroveCatLink).exists()).toBe(true);
    });
});
