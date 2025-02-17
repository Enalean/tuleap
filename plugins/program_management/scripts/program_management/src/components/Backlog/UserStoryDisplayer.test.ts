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

import UserStoryDisplayer from "./UserStoryDisplayer.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { UserStory } from "../../helpers/UserStories/user-stories-retriever";
import type { Project, TrackerMinimalRepresentation } from "../../type";
import type { ConfigurationState } from "../../store/configuration";
import { createConfigurationModule } from "../../store/configuration";

describe("UserStoryDisplayer", () => {
    const getWrapper = (user_story?: Partial<UserStory>, accessibility = false): VueWrapper => {
        const defaulted_user_story = {
            id: 14,
            title: "My US",
            xref: "us #14",
            background_color: "lake-placid-blue",
            tracker: { color_name: "fiesta-red" } as TrackerMinimalRepresentation,
            is_open: true,
            uri: "tracker?aid=14",
            project: { label: "project" } as Project,
            ...user_story,
        };

        return shallowMount(UserStoryDisplayer, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: createConfigurationModule({
                            accessibility,
                        } as ConfigurationState),
                    },
                }),
            },
            props: { user_story: defaulted_user_story },
        });
    };

    it("Displays user story with accessibility", () => {
        const wrapper = getWrapper({}, true);

        const card_classes = wrapper.get("[data-test=user-story-card]").classes();
        expect(card_classes).toContain("element-card-with-accessibility");
        expect(card_classes).toContain("element-card-fiesta-red");
        expect(card_classes).toContain("element-card-background-lake-placid-blue");
        expect(wrapper.find("[data-test=user-story-accessibility]").exists()).toBe(true);
    });

    it("Displays user story without accessibility", () => {
        const wrapper = getWrapper();

        const card_classes = wrapper.get("[data-test=user-story-card]").classes();
        expect(card_classes).not.toContain("element-card-with-accessibility");
        expect(wrapper.find("[data-test=user-story-accessibility]").exists()).toBe(false);
    });

    it("Displays a closed user story with accessibility", () => {
        const wrapper = getWrapper({ is_open: false }, true);

        const card_classes = wrapper.get("[data-test=user-story-card]").classes();
        expect(card_classes).toContain("element-card-with-accessibility");
        expect(card_classes).toContain("element-card-closed");
        expect(wrapper.find("[data-test=user-story-accessibility]").exists()).toBe(true);
    });
});
