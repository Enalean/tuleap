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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FeatureCard from "./FeatureCard.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import FeatureCardBacklogItems from "./FeatureCardBacklogItems.vue";
import type { Feature } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";
import { createConfigurationModule } from "../../../store/configuration";

describe("FeatureCard", () => {
    const getWrapper = (
        feature?: Partial<Feature>,
        configuration?: Partial<ConfigurationState>,
        user_can_plan = true,
    ): VueWrapper => {
        const defaulted_feature = {
            id: 100,
            title: "My artifact",
            tracker: { label: "bug", color_name: "lake-placid-blue" },
            is_open: true,
            background_color: "",
            has_user_story_planned: false,
            has_user_story_linked: false,
            ...feature,
        } as Feature;

        const defaulted_configuration = {
            accessibility: false,
            can_create_program_increment: true,
            has_plan_permissions: true,
            ...configuration,
        } as ConfigurationState;

        return shallowMount(FeatureCard, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: createConfigurationModule(defaulted_configuration),
                    },
                }),
            },
            props: {
                feature: defaulted_feature,
                program_increment: { user_can_plan } as ProgramIncrement,
            },
        });
    };

    it("Displays a draggable card with accessibility pattern", () => {
        const wrapper = getWrapper(
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
        const card = wrapper.get("[data-test=feature-card]");
        expect(card.classes()).toContain("element-draggable-item");
        expect(card.classes()).toContain("element-card-sherwood-green");
        expect(card.classes()).toContain("element-card-with-accessibility");
        expect(card.classes()).toContain("element-card-background-peggy-pink");
    });

    it(`Adds a closed class to the card`, () => {
        const wrapper = getWrapper({ is_open: false });
        expect(wrapper.get("[data-test=feature-card]").classes()).toContain("element-card-closed");
    });

    it("Displays a card without accessibility pattern", () => {
        const wrapper = getWrapper({}, { accessibility: false });
        expect(wrapper.get("[data-test=feature-card]").classes()).not.toContain(
            "element-card-with-accessibility",
        );
    });

    it("Displays a not draggable card when user can not plan/unplan features", () => {
        const wrapper = getWrapper({}, { has_plan_permissions: false });
        const card = wrapper.get("[data-test=feature-card]");
        const tooltip = wrapper.get("[data-test=card-tooltip]");
        expect(card.classes()).not.toContain("element-draggable-item");
        expect(card.classes()).not.toContain("element-card-with-accessibility");
        expect(tooltip.classes()).toContain("tlp-tooltip");
    });

    it(`Displays a not draggable card when user cannot plan in the given program increment`, () => {
        const wrapper = getWrapper({}, { has_plan_permissions: true }, false);
        const card = wrapper.get("[data-test=feature-card]");
        const tooltip = wrapper.get("[data-test=card-tooltip]");
        expect(card.classes()).not.toContain("element-draggable-item");
        expect(card.classes()).not.toContain("element-card-with-accessibility");
        expect(tooltip.classes()).toContain("tlp-tooltip");
    });

    it("Displays a not draggable card with items backlog container", () => {
        const wrapper = getWrapper({ has_user_story_linked: true }, {}, false);
        expect(wrapper.findComponent(FeatureCardBacklogItems).exists()).toBe(true);
    });
});
