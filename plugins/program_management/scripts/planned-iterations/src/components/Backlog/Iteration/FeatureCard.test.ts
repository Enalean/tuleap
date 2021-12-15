/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { createPlanIterationsLocalVue } from "../../../helpers/local-vue-for-test";

import FeatureCard from "./FeatureCard.vue";

import type { Wrapper } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";

describe("FeatureCard", () => {
    async function getWrapper(
        is_accessibility_mode_enabled: boolean
    ): Promise<Wrapper<FeatureCard>> {
        return shallowMount(FeatureCard, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                feature: {
                    background_color: "peggy-pink",
                    has_user_story_planned: false,
                    has_user_story_linked: false,
                    is_open: true,
                    id: 101,
                    uri: "/uri/of/feature",
                    xref: "feature #101",
                    title: "Feature 101",
                    tracker: {
                        color_name: "red-wine",
                    },
                    project: {
                        id: 101,
                        uri: "uri/to/g-pig",
                        label: "Guinea Pigs",
                        icon: "🐹",
                    },
                },
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        is_accessibility_mode_enabled,
                    },
                }),
            },
        });
    }

    it("should display the feature as a card with tracker and background color", async () => {
        const wrapper = await getWrapper(true);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("should not display accessiblity patterns when the mode is disabled", async () => {
        const wrapper = await getWrapper(false);
        expect(wrapper.find("[data-test=element-card-accessibility]").exists()).toBe(false);
    });
});
