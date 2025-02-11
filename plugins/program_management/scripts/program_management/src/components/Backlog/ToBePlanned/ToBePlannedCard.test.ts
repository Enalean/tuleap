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
import ToBePlannedCard from "./ToBePlannedCard.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { Feature } from "../../../type";
import ToBePlannedBacklogItems from "./ToBePlannedBacklogItems.vue";
import type { ConfigurationState } from "../../../store/configuration";
import { createConfigurationModule } from "../../../store/configuration";

describe("ToBePlannedCard", () => {
    function getWrapper(
        feature?: Partial<Feature>,
        configuration?: Partial<ConfigurationState>,
    ): VueWrapper {
        return shallowMount(ToBePlannedCard, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: createConfigurationModule({
                            accessibility: false,
                            has_plan_permissions: true,
                            ...configuration,
                        } as ConfigurationState),
                    },
                }),
            },
            props: {
                feature: {
                    id: 100,
                    title: "My artifact",
                    tracker: { label: "bug", color_name: "lake-placid-blue" },
                    background_color: "peggy-pink",
                    has_user_story_linked: false,
                    ...feature,
                } as Feature,
            },
        });
    }

    it("Displays a draggable card with accessibility pattern", () => {
        const wrapper = getWrapper(
            {
                background_color: "peggy-pink",
                tracker: {
                    id: 414,
                    uri: "/uri/to/tracker",
                    label: "bug",
                    color_name: "sherwood-green",
                },
            },
            { accessibility: true, has_plan_permissions: true },
        );
        const card = wrapper.find("[data-test=to-be-planned-card]");
        expect(card.classes()).toContain("element-draggable-item");
        expect(card.classes()).toContain("element-card-sherwood-green");
        expect(card.classes()).toContain("element-card-with-accessibility");
        expect(card.classes()).toContain("element-card-background-peggy-pink");
    });

    it("Displays a not draggable card without accessibility pattern", () => {
        const wrapper = getWrapper({}, { accessibility: false });
        expect(wrapper.find("[data-test=to-be-planned-card]").classes()).not.toContain(
            "element-card-with-accessibility",
        );
    });

    it(`Displays a not draggable card when user cannot plan/unplan features`, () => {
        const wrapper = getWrapper({}, { has_plan_permissions: false });
        const card = wrapper.find("[data-test=to-be-planned-card]");
        expect(card.classes()).toContain("element-not-draggable");
        expect(card.classes()).not.toContain("element-draggable-item");
        expect(card.attributes("title")).not.toBe("");
    });

    it("Displays a draggable card with backlog items container", () => {
        const wrapper = getWrapper({ has_user_story_linked: true });
        expect(wrapper.findComponent(ToBePlannedBacklogItems).exists()).toBe(true);
    });
});
