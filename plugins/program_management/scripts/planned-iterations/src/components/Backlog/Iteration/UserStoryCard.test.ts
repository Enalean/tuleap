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

import UserStoryCard from "./UserStoryCard.vue";

import type { Wrapper } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Feature } from "../../../type";

describe("UserStoryCard", () => {
    async function getWrapper(
        is_accessibility_mode_enabled: boolean,
    ): Promise<Wrapper<UserStoryCard>> {
        return shallowMount(UserStoryCard, {
            localVue: await createPlanIterationsLocalVue(),
            propsData: {
                user_story: {
                    background_color: "peggy-pink",
                    is_open: true,
                    id: 101,
                    uri: "/uri/of/user_story",
                    xref: "user_story #101",
                    title: "User Story 101",
                    tracker: {
                        color_name: "red-wine",
                    },
                    project: {
                        id: 101,
                        uri: "uri/to/g-pig",
                        label: "Guinea Pigs",
                        icon: "🐹",
                    },
                    feature: {
                        title: "My parent feature",
                        uri: "uri/to/feature",
                    } as Feature,
                },
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            is_accessibility_mode_enabled,
                        },
                    },
                }),
            },
        });
    }

    it("should display the user_story as a card with tracker and background color", async () => {
        const wrapper = await getWrapper(true);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("should not display accessiblity patterns when the mode is disabled", async () => {
        const wrapper = await getWrapper(false);
        expect(wrapper.find("[data-test=element-card-accessibility]").exists()).toBe(false);
    });
});
