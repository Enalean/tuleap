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
import ToBePlannedBacklogItems from "./ToBePlannedBacklogItems.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { Feature, TrackerMinimalRepresentation } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { UserStory } from "../../../helpers/UserStories/user-stories-retriever";
import ErrorDisplayer from "../ErrorDisplayer.vue";
import UserStoryDisplayer from "../UserStoryDisplayer.vue";
import type { DefaultData } from "vue/types/options";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";

describe("ToBePlannedBacklogItems", () => {
    let component_options: ShallowMountOptions<ToBePlannedBacklogItems>;
    let store: Store;

    beforeEach(() => {
        store = createStoreMock({
            state: {},
        });
    });
    it("Displays a skeleton during get user stories", async () => {
        component_options = {
            propsData: {
                to_be_planned_element: {
                    id: 100,
                } as Feature,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: store,
            },
        };

        jest.spyOn(store, "dispatch").mockReturnValue(Promise.resolve([]));

        const wrapper = shallowMount(ToBePlannedBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        wrapper.setData({
            user_stories: [],
            is_loading_user_story: true,
            message_error_rest: "",
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBeTruthy();
    });

    it("Displays error message if api rest error exists", async () => {
        component_options = {
            data(): DefaultData<ToBePlannedBacklogItems> {
                return {
                    message_error_rest: "404 Not Found",
                };
            },
            propsData: {
                to_be_planned_element: {
                    id: 100,
                    user_stories: [{ id: 14 } as UserStory],
                } as Feature,
            },
            localVue: await createProgramManagementLocalVue(),
        };

        const wrapper = shallowMount(ToBePlannedBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(ErrorDisplayer).exists()).toBeTruthy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeFalsy();
    });

    it("When user stories are loaded, Then UserStoryDisplayer is rendered", async () => {
        component_options = {
            propsData: {
                to_be_planned_element: {
                    id: 100,
                } as Feature,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: store,
            },
        };

        jest.spyOn(store, "dispatch").mockReturnValue(
            Promise.resolve([
                {
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
            ])
        );

        const wrapper = shallowMount(ToBePlannedBacklogItems, component_options);

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick(); // Load User Stories
        await wrapper.vm.$nextTick(); // Display User Stories

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(ErrorDisplayer).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists).toBeTruthy();
    });

    it("No rest call when user stories are already loaded in feature and user can hide user stories", async () => {
        component_options = {
            propsData: {
                to_be_planned_element: {
                    id: 100,
                    user_stories: [
                        {
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
                    ],
                } as Feature,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: store,
            },
        };
        const dispatchSpy = jest.spyOn(store, "dispatch");

        const wrapper = await shallowMount(ToBePlannedBacklogItems, component_options);
        expect(dispatchSpy).not.toHaveBeenCalled();

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(ErrorDisplayer).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists).toBeTruthy();

        wrapper.find("[data-test=backlog-items-open-close-button]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(BacklogElementSkeleton).exists()).toBeFalsy();
        expect(wrapper.findComponent(ErrorDisplayer).exists()).toBeFalsy();
        expect(wrapper.findComponent(UserStoryDisplayer).exists()).toBeFalsy();
    });
});
