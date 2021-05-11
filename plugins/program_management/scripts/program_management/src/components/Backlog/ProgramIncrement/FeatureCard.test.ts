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
import FeatureCard from "./FeatureCard.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import type { Feature } from "../../../type";

describe("FeatureCard", () => {
    let component_options: ShallowMountOptions<FeatureCard>;

    it("Displays a draggable card with accessibility pattern", async () => {
        component_options = {
            propsData: {
                feature: {
                    id: 100,
                    title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "peggy_pink_text",
                    has_user_story_planned: false,
                } as Feature,
                program_increment: {
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: true, can_create_program_increment: true },
                        ongoing_move_elements_id: [],
                    },
                }),
            },
        };

        const wrapper = shallowMount(FeatureCard, component_options);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a not draggable card without accessibility pattern", async () => {
        component_options = {
            propsData: {
                feature: {
                    id: 100,
                    title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "",
                    has_user_story_planned: false,
                } as Feature,
                program_increment: {
                    user_can_plan: true,
                } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            accessibility: false,
                            can_create_program_increment: false,
                        },
                        ongoing_move_elements_id: [],
                    },
                }),
            },
        };

        const wrapper = shallowMount(FeatureCard, component_options);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a not draggable card when user can not plan/unplan features", async () => {
        component_options = {
            propsData: {
                feature: {
                    id: 100,
                    title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "",
                    has_user_story_planned: true,
                } as Feature,
                program_increment: {
                    user_can_plan: false,
                } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: false, can_create_program_increment: true },
                        ongoing_move_elements_id: [],
                    },
                }),
            },
        };

        const wrapper = shallowMount(FeatureCard, component_options);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a draggable card with items backlog container", async () => {
        component_options = {
            propsData: {
                feature: {
                    id: 100,
                    title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "",
                    has_user_story_planned: true,
                    has_user_story_linked: true,
                } as Feature,
                program_increment: {
                    user_can_plan: false,
                } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: false, can_create_program_increment: true },
                        ongoing_move_elements_id: [],
                    },
                }),
            },
        };

        const wrapper = shallowMount(FeatureCard, component_options);
        expect(wrapper.findComponent(FeatureCardBacklogItems).exists()).toBeTruthy();
    });

    it("Displays a card when it is moving", async () => {
        component_options = {
            propsData: {
                feature: {
                    id: 100,
                    title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "",
                    has_user_story_planned: true,
                    has_user_story_linked: true,
                } as Feature,
                program_increment: {
                    user_can_plan: false,
                } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility: false, can_create_program_increment: true },
                        ongoing_move_elements_id: [100],
                    },
                }),
            },
        };

        jest.useFakeTimers();

        const wrapper = shallowMount(FeatureCard, component_options);

        expect(wrapper.findComponent(FeatureCardBacklogItems).exists()).toBeTruthy();
        expect(wrapper.find("[data-test=feature-card]").classes()).toContain("is-moving");
        expect(wrapper.find("[data-test=feature-card]").classes()).not.toContain("has-moved");

        wrapper.vm.$store.state.ongoing_move_elements_id = [];
        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=feature-card]").classes()).toContain("has-moved");
        expect(wrapper.find("[data-test=feature-card]").classes()).not.toContain("is-moving");

        jest.advanceTimersByTime(1000);
        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=feature-card]").classes()).not.toContain("has-moved");
        expect(wrapper.find("[data-test=feature-card]").classes()).not.toContain("is-moving");
    });
});
