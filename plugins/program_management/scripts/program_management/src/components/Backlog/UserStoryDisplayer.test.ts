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
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createProgramManagementLocalVue } from "../../helpers/local-vue-for-test";
import type { UserStory } from "../../helpers/UserStories/user-stories-retriever";
import type { Project, TrackerMinimalRepresentation } from "../../type";

describe("UserStoryDisplayer", () => {
    const getWrapper = async (
        user_story?: Partial<UserStory>,
        accessibility = false,
    ): Promise<Wrapper<UserStoryDisplayer>> => {
        const defaulted_user_story = {
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
            } as Project,
            ...user_story,
        };

        const component_options = {
            propsData: {
                user_story: defaulted_user_story,
            },
            localVue: await createProgramManagementLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: { accessibility },
                    },
                }),
            },
        };

        return shallowMount(UserStoryDisplayer, component_options);
    };

    it("Displays user story with accessibility", async () => {
        const wrapper = await getWrapper({}, true);

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays user story without accessibility", async () => {
        const wrapper = await getWrapper();

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a closed user story with accessibility", async () => {
        const wrapper = await getWrapper({ is_open: false }, true);

        expect(wrapper.element).toMatchSnapshot();
    });
});
