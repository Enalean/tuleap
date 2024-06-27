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
import ProjectName from "./ProjectName.vue";
import emitter from "../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ProjectName", () => {
    function createWrapper(): VueWrapper {
        const component_options = {
            global: {
                ...getGlobalTestOptions(),
            },
        };

        return shallowMount(ProjectName, component_options);
    }

    it(`Should not yields again user, if he just started to type its new project short name, even if the minimal length is not reached`, () => {
        const wrapper = createWrapper();
        wrapper.get("[data-test=new-project-name]").setValue("t");

        expect(wrapper.find("[data-test=project-name-is-invalid]").exists()).toBe(false);
    });

    it(`Emit a named event`, () => {
        const event_bus_emit = jest.spyOn(emitter, "emit");

        const wrapper = createWrapper();
        wrapper.get("[data-test=new-project-name]").setValue("test");

        expect(wrapper.find("[data-test=project-project-name-is-invalid]").exists()).toBe(false);

        expect(event_bus_emit).toHaveBeenCalledWith("slugify-project-name", "test");
    });
});
