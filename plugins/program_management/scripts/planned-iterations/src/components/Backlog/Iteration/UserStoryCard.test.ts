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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import UserStoryCard from "./UserStoryCard.vue";
import type { Feature } from "../../../type";

describe("UserStoryCard", () => {
    function getWrapper(
        is_accessibility_mode_enabled: boolean,
    ): VueWrapper<InstanceType<typeof UserStoryCard>> {
        const store_options = {
            modules: {
                configuration: {
                    namespaced: true,
                    state: { is_accessibility_mode_enabled },
                },
            },
        };
        return shallowMount(UserStoryCard, {
            global: { ...getGlobalTestOptions(store_options) },
            props: {
                user_story: {
                    background_color: "peggy-pink",
                    is_open: true,
                    id: 101,
                    uri: "/uri/of/user_story",
                    xref: "user_story #101",
                    title: "User Story 101",
                    tracker: { color_name: "red-wine" },
                    project: { id: 101, uri: "uri/to/g-pig", label: "Guinea Pigs", icon: "🐹" },
                    feature: { title: "My parent feature", uri: "uri/to/feature" } as Feature,
                },
            },
        });
    }

    it("should display the user_story as a card with tracker and background color", () => {
        const wrapper = getWrapper(true);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("should not display accessibility patterns when the mode is disabled", () => {
        const wrapper = getWrapper(false);
        expect(wrapper.find("[data-test=element-card-accessibility]").exists()).toBe(false);
    });
});
