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

import type { ShallowMountOptions } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { ProgramElement } from "../../../type";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import ProgramIncrementSkeleton from "../ProgramIncrement/ProgramIncrementSkeleton.vue";
import * as UserStoryRetriever from "../../../helpers/BacklogItems/children-feature-retriever";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import type { UserStory } from "../../../helpers/BacklogItems/children-feature-retriever";
import BacklogItemsErrorShow from "../BacklogItemsErrorShow.vue";
import UserStoryDisplayer from "../UserStoryDisplayer.vue";
import type { DefaultData } from "vue/types/options";

describe("FeatureCardBacklogItems", () => {
    let component_options: ShallowMountOptions<FeatureCardBacklogItems>;

    it("Displays a skeleton during get user stories", async () => {
        component_options = {
            propsData: {
                feature: {
                    artifact_id: 100,
                } as ProgramElement,
                program_increment: { id: 11 } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        };

        const getLinkedUserStoriesToFeature = jest.spyOn(
            UserStoryRetriever,
            "getLinkedUserStoriesToFeature"
        );
        getLinkedUserStoriesToFeature.mockImplementation(() => Promise.resolve([]));

        const wrapper = shallowMount(FeatureCardBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        wrapper.setData({
            user_stories: [],
            is_loading_user_story: true,
            message_error_rest: "",
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ProgramIncrementSkeleton).exists()).toBeTruthy();
    });

    it("Displays error message if api rest error exists", async () => {
        component_options = {
            data(): DefaultData<FeatureCardBacklogItems> {
                return {
                    message_error_rest: "404 Not Found",
                };
            },
            propsData: {
                feature: {
                    artifact_id: 100,
                    user_stories: [{ id: 14 } as UserStory],
                } as ProgramElement,
                program_increment: { id: 11 } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
        };

        const wrapper = shallowMount(FeatureCardBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ProgramIncrementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(BacklogItemsErrorShow).exists()).toBeTruthy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeFalsy();
    });

    it("When user stories are loaded, Then UserStoryDisplayer is rendered", async () => {
        component_options = {
            propsData: {
                feature: {
                    artifact_id: 100,
                } as ProgramElement,
                program_increment: { id: 11 } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        };

        const getLinkedUserStoriesToFeature = jest.spyOn(
            UserStoryRetriever,
            "getLinkedUserStoriesToFeature"
        );
        getLinkedUserStoriesToFeature.mockImplementation(() =>
            Promise.resolve([
                {
                    id: 14,
                    title: "My US",
                    xref: "us #14",
                    background_color: "lake-placid-blue",
                    color_xref_name: "fiesta-red",
                    is_open: true,
                    uri: "tracker?aid=14",
                    project: {
                        label: "project",
                    },
                } as UserStory,
            ])
        );

        const wrapper = await shallowMount(FeatureCardBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick(); // Init the component & load user stories
        await wrapper.vm.$nextTick(); // Display user stories

        expect(wrapper.findComponent(ProgramIncrementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(BacklogItemsErrorShow).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeTruthy();
    });

    it("No rest call when user stories are already loaded in feature", async () => {
        component_options = {
            propsData: {
                feature: {
                    artifact_id: 100,
                    user_stories: [
                        {
                            id: 14,
                            title: "My US",
                            xref: "us #14",
                            background_color: "lake-placid-blue",
                            color_xref_name: "fiesta-red",
                            is_open: true,
                            uri: "tracker?aid=14",
                            project: {
                                label: "project",
                            },
                        } as UserStory,
                    ],
                } as ProgramElement,
                program_increment: { id: 11 } as ProgramIncrement,
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

        const wrapper = await shallowMount(FeatureCardBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ProgramIncrementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(BacklogItemsErrorShow).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeTruthy();
    });

    it("When user stories are loaded, Then user can hide stories", async () => {
        component_options = {
            propsData: {
                feature: {
                    artifact_id: 100,
                    user_stories: [
                        {
                            id: 14,
                            title: "My US",
                            xref: "us #14",
                            background_color: "lake-placid-blue",
                            color_xref_name: "fiesta-red",
                            is_open: true,
                            uri: "tracker?aid=14",
                            project: {
                                label: "project",
                            },
                        } as UserStory,
                    ],
                } as ProgramElement,
                program_increment: { id: 11 } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {},
                }),
            },
        };

        const wrapper = await shallowMount(FeatureCardBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ProgramIncrementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(BacklogItemsErrorShow).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeTruthy();

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(ProgramIncrementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(BacklogItemsErrorShow).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeFalsy();
    });
});
