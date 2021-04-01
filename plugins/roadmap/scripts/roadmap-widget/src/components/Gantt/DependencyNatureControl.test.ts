/**
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DependencyNatureControl from "./DependencyNatureControl.vue";
import { createRoadmapLocalVue } from "../../helpers/local-vue-for-test";
import { NaturesLabels } from "../../type";

function isSelected(wrapper: Wrapper<DependencyNatureControl>, nature: string): boolean {
    const option = wrapper.find(`[data-test=option-${nature}]`).element;
    if (!(option instanceof HTMLOptionElement)) {
        throw Error("Enable to find option for nature " + nature);
    }

    return option.selected;
}

describe("DependencyNatureControl", () => {
    it("should display a selectbox with available natures", async () => {
        const wrapper = shallowMount(DependencyNatureControl, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                value: null,
                available_natures: new NaturesLabels([
                    ["", "Linked to"],
                    ["depends_on", "Depends on"],
                ]),
            },
        });

        expect(isSelected(wrapper, "none")).toBe(true);
        expect(isSelected(wrapper, "")).toBe(false);
        expect(isSelected(wrapper, "depends_on")).toBe(false);
    });

    it("should emit input event when the value is changed", async () => {
        const wrapper = shallowMount(DependencyNatureControl, {
            localVue: await createRoadmapLocalVue(),
            propsData: {
                value: null,
                available_natures: new NaturesLabels([
                    ["", "Linked to"],
                    ["depends_on", "Depends on"],
                ]),
            },
        });

        wrapper.find(`[data-test=option-depends_on]`).setSelected();
        wrapper.find(`[data-test=option-]`).setSelected();
        wrapper.find(`[data-test=option-none]`).setSelected();

        const input_event = wrapper.emitted("input");
        if (!input_event) {
            throw new Error("Failed to catch input event");
        }

        expect(input_event[0][0]).toBe("depends_on");
        expect(input_event[1][0]).toBe("");
        expect(input_event[2][0]).toBe(null);
    });
});
