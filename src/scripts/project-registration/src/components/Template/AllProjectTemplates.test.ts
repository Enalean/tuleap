/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ProjectList from "./AllProjectTemplates.vue";
import NewProjectBoxes from "./NewProjectBoxesSvg.vue";
import TemplateSelection from "./TemplateSelection.vue";
import TemplateFooter from "./TemplateFooter.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("AllProjectTemplates", () => {
    let factory: VueWrapper;
    beforeEach(() => {
        factory = shallowMount(ProjectList, {
            global: {
                ...getGlobalTestOptions(),
            },
        });
    });
    it("Spawns the AllProjectTemplates component", () => {
        const wrapper = factory;

        expect(wrapper.findComponent(NewProjectBoxes).exists()).toBe(true);
        expect(wrapper.findComponent(TemplateSelection).exists()).toBe(true);
        expect(wrapper.findComponent(TemplateFooter).exists()).toBe(true);
    });
});
