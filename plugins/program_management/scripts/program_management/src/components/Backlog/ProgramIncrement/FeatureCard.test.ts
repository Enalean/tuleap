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
import FeatureCard from "./FeatureCard.vue";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import type { Feature } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";

describe("FeatureCard", () => {
    const getWrapper = async (
        feature?: Partial<Feature>,
        configuration?: Partial<ConfigurationState>,
        user_can_plan = true,
        ongoing_move_elements_id: number[] = [],
    ): Promise<Wrapper<FeatureCard>> => {
        const defaulted_feature = {
            id: 100,
            title: "My artifact",
            tracker: {
                label: "bug",
                color_name: "lake_placid_blue",
            },
            is_open: true,
            background_color: "",
            has_user_story_planned: false,
            has_user_story_linked: false,
            ...feature,
        };

        const defaulted_configuration = {
            accessibility: false,
            has_plan_permissions: true,
            ...configuration,
        };

        const component_options = {
            propsData: {
                feature: defaulted_feature,
                program_increment: {
                    user_can_plan,
                } as ProgramIncrement,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: defaulted_configuration,
                        ongoing_move_elements_id,
                    },
                }),
            },
        };
        return shallowMount(FeatureCard, component_options);
    };

    it("Displays a draggable card with accessibility pattern", async () => {
        const wrapper = await getWrapper(
            { background_color: "peggy_pink_text" },
            { accessibility: true },
        );
        expect(wrapper.element).toMatchSnapshot();
    });

    it(`Adds a closed class to the card`, async () => {
        const wrapper = await getWrapper({ is_open: false });
        const card = wrapper.find("[data-test=feature-card]");
        expect(card.classes()).toContain("element-card-closed");
    });

    it("Displays a not draggable card without accessibility pattern", async () => {
        const wrapper = await getWrapper({}, { has_plan_permissions: false });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a not draggable card when user can not plan/unplan features", async () => {
        const wrapper = await getWrapper({ has_user_story_planned: true }, {}, false);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a draggable card with items backlog container", async () => {
        const wrapper = await getWrapper(
            { has_user_story_planned: true, has_user_story_linked: true },
            {},
            false,
        );
        expect(wrapper.findComponent(FeatureCardBacklogItems).exists()).toBeTruthy();
    });
});
