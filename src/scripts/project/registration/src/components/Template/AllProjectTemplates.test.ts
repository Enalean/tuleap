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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import ProjectList from "./AllProjectTemplates.vue";
import NewProjectBoxes from "./NewProjectBoxesSvg.vue";
import TuleapTemplateList from "./Tuleap/TuleapTemplateList.vue";
import TemplateFooter from "./TemplateFooter.vue";
import CompanyTemplateList from "./Company/CompanyTemplateList.vue";
import AdvancedTemplateList from "./Advanced/AdvancedTemplateList.vue";

describe("ProjectList", () => {
    let factory: Wrapper<ProjectList>;
    beforeEach(async () => {
        factory = shallowMount(ProjectList, {
            localVue: await createProjectRegistrationLocalVue(),
        });
    });
    it("Spawns the ProjectTemplates component", () => {
        const wrapper = factory;

        expect(wrapper.contains(NewProjectBoxes)).toBe(true);
        expect(wrapper.contains(TuleapTemplateList)).toBe(true);
        expect(wrapper.contains(TemplateFooter)).toBe(true);
        expect(wrapper.contains(CompanyTemplateList)).toBe(true);
        expect(wrapper.contains(AdvancedTemplateList)).toBe(true);
    });
});
