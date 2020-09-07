/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import { Project } from "../../../type";
import ProjectLink from "./ProjectLink.vue";
import { createStoreMock } from "../../../../../vue-components/store-wrapper-jest";
import { State } from "../../../store/type";

describe("ProjectLink", () => {
    it("Displays the link to a project", () => {
        const wrapper = shallowMount(ProjectLink, {
            propsData: {
                project: {
                    is_public: true,
                    project_name: "Guinea Pig",
                    project_uri: "/pojects/gpig",
                } as Project,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        are_restricted_users_allowed: true,
                    } as State,
                }),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
