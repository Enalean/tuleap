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

import type { ShallowMountOptions } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ElementCard from "./ElementCard.vue";
import { createProgramManagementLocalVue } from "../../helpers/local-vue-for-test";
import type { ToBePlannedElement } from "../../helpers/ToBePlanned/element-to-plan-retriever";
import * as configuration from "../../configuration";

describe("ElementCard", () => {
    let component_options: ShallowMountOptions<ElementCard>;

    it("Displays a card with accessibility pattern", async () => {
        jest.spyOn(configuration, "userHasAccessibilityMode").mockReturnValue(true);
        component_options = {
            propsData: {
                element: {
                    artifact_id: 100,
                    artifact_title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "peggy_pink_text",
                } as ToBePlannedElement,
            },
            localVue: await createProgramManagementLocalVue(),
        };

        const wrapper = shallowMount(ElementCard, component_options);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays a card without accessibility pattern", async () => {
        jest.spyOn(configuration, "userHasAccessibilityMode").mockReturnValue(false);
        component_options = {
            propsData: {
                element: {
                    artifact_id: 100,
                    artifact_title: "My artifact",
                    tracker: {
                        label: "bug",
                        color_name: "lake_placid_blue",
                    },
                    background_color: "",
                } as ToBePlannedElement,
            },
            localVue: await createProgramManagementLocalVue(),
        };

        const wrapper = shallowMount(ElementCard, component_options);
        expect(wrapper.element).toMatchSnapshot();
    });
});
