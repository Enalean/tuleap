/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import UserStoryDisplayer from "./UserStoryDisplayer.vue";
import type { ShallowMountOptions } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import { createProgramManagementLocalVue } from "../../helpers/local-vue-for-test";
import type { UserStory } from "../../helpers/UserStories/user-stories-retriever";
import type { Project, TrackerMinimalRepresentation } from "../../type";

describe("UserStoryDisplayer", () => {
    let component_options: ShallowMountOptions<UserStoryDisplayer>;

    it("Displays user story with accessibility", async () => {
        component_options = {
            propsData: {
                user_story: {
                    id: 14,
                    title: "My US",
                    xref: "us #14",
                    background_color: "lake-placid-blue",
                    tracker: {
                        color_name: "fiesta-red",
                    } as TrackerMinimalRepresentation,
                    is_open: true,
                    uri: "tracker?aid=14",
                    project: {
                        label: "project",
                    } as Project,
                } as UserStory,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: true },
                    },
                }),
            },
        };

        const wrapper = shallowMount(UserStoryDisplayer, component_options);

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays user story without accessibility", async () => {
        component_options = {
            propsData: {
                user_story: {
                    id: 14,
                    title: "My US",
                    xref: "us #14",
                    background_color: "lake-placid-blue",
                    tracker: {
                        color_name: "fiesta-red",
                    } as TrackerMinimalRepresentation,
                    is_open: true,
                    uri: "tracker?aid=14",
                    project: {
                        label: "project",
                    },
                } as UserStory,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: false },
                    },
                }),
            },
        };

        const wrapper = shallowMount(UserStoryDisplayer, component_options);

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a closed user story with accessibility", async () => {
        component_options = {
            propsData: {
                user_story: {
                    id: 14,
                    title: "My US",
                    xref: "us #14",
                    background_color: "lake-placid-blue",
                    tracker: {
                        color_name: "fiesta-red",
                    } as TrackerMinimalRepresentation,
                    is_open: false,
                    uri: "tracker?aid=14",
                    project: {
                        label: "project",
                    },
                } as UserStory,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: true },
                    },
                }),
            },
        };

        const wrapper = shallowMount(UserStoryDisplayer, component_options);

        expect(wrapper.element).toMatchSnapshot();
    });
});
