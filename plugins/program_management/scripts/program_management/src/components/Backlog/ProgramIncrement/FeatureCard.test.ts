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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import FeatureCard from "./FeatureCard.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import type { Feature } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";

describe("FeatureCard", () => {
    const getWrapper = async (
        feature?: Partial<Feature>,
        configuration?: Partial<ConfigurationState>,
        user_can_plan = true,
    ): Promise<Wrapper<Vue>> => {
        const defaulted_feature = {
            id: 100,
            title: "My artifact",
            tracker: { label: "bug", color_name: "lake-placid-blue" },
            is_open: true,
            background_color: "",
            has_user_story_planned: false,
            has_user_story_linked: false,
            ...feature,
        };

        const defaulted_configuration = {
            accessibility: false,
            can_create_program_increment: true,
            has_plan_permissions: true,
            ...configuration,
        };

        const component_options = {
            localVue: await createProgramManagementLocalVue(),
            propsData: {
                feature: defaulted_feature,
                program_increment: { user_can_plan } as ProgramIncrement,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: defaulted_configuration,
                    },
                }),
            },
        };
        return shallowMount(FeatureCard, component_options);
    };

    it("Displays a draggable card with accessibility pattern", async () => {
        const wrapper = await getWrapper(
            {
                background_color: "peggy-pink",
                tracker: {
                    id: 475,
                    uri: "/uri/to/tracker",
                    label: "bug",
                    color_name: "sherwood-green",
                },
            },
            { accessibility: true },
        );
        const card = wrapper.find("[data-test=feature-card]");
        expect(card.classes()).toContain("element-draggable-item");
        expect(card.classes()).toContain("element-card-sherwood-green");
        expect(card.classes()).toContain("element-card-with-accessibility");
        expect(card.classes()).toContain("element-card-background-peggy-pink");
    });

    it(`Adds a closed class to the card`, async () => {
        const wrapper = await getWrapper({ is_open: false });
        expect(wrapper.find("[data-test=feature-card]").classes()).toContain("element-card-closed");
    });

    it("Displays a card without accessibility pattern", async () => {
        const wrapper = await getWrapper({}, { accessibility: false });
        expect(wrapper.find("[data-test=feature-card]").classes()).not.toContain(
            "element-card-with-accessibility",
        );
    });

    it("Displays a not draggable card when user can not plan/unplan features", async () => {
        const wrapper = await getWrapper({}, { has_plan_permissions: false });
        const card = wrapper.find("[data-test=feature-card]");
        const tooltip = wrapper.find("[data-test=card-tooltip]");
        expect(card.classes()).not.toContain("element-draggable-item");
        expect(card.classes()).not.toContain("element-card-with-accessibility");
        expect(tooltip.classes()).toContain("tlp-tooltip");
    });

    it(`Displays a not draggable card when user cannot plan in the given program increment`, async () => {
        const wrapper = await getWrapper({}, { has_plan_permissions: true }, false);
        const card = wrapper.find("[data-test=feature-card]");
        const tooltip = wrapper.find("[data-test=card-tooltip]");
        expect(card.classes()).not.toContain("element-draggable-item");
        expect(card.classes()).not.toContain("element-card-with-accessibility");
        expect(tooltip.classes()).toContain("tlp-tooltip");
    });

    it("Displays a not draggable card with items backlog container", async () => {
        const wrapper = await getWrapper({ has_user_story_linked: true }, {}, false);
        expect(wrapper.findComponent(FeatureCardBacklogItems).exists()).toBe(true);
    });
});
