/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import TrackerFromJiraProject from "./TrackerFromJiraProject.vue";
import { createTrackerCreationLocalVue } from "../../../../../helpers/local-vue-for-tests";
import { shallowMount } from "@vue/test-utils";
import { ProjectList } from "../../../../../store/type";

describe("TrackerFromJiraProject", () => {
    it("renders the component", async () => {
        const wrapper = shallowMount(TrackerFromJiraProject, {
            localVue: await createTrackerCreationLocalVue(),
            propsData: {
                project_list: [
                    { id: "TO", label: "toto" } as ProjectList,
                    { id: "TU", label: "tutu" } as ProjectList,
                ],
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
