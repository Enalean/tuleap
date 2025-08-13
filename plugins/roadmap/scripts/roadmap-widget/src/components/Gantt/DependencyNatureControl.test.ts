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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { NaturesLabels } from "../../type";
import type { TasksState } from "../../store/tasks/type";
import type { RootState } from "../../store/type";
import DependencyNatureControl from "./DependencyNatureControl.vue";

function isSelected(wrapper: VueWrapper, nature: string): boolean {
    const option = wrapper.find(`[data-test=option-${nature}]`).element;
    if (!(option instanceof HTMLOptionElement)) {
        throw Error("Enable to find option for nature " + nature);
    }

    return option.selected;
}

describe("DependencyNatureControl", () => {
    let value: string | null, available_natures: NaturesLabels, has_at_least_one_row_shown: boolean;

    beforeEach(() => {
        value = null;
        available_natures = new NaturesLabels([
            ["", "Linked to"],
            ["depends_on", "Depends on"],
        ]);
        has_at_least_one_row_shown = true;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(DependencyNatureControl, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        tasks_state: {} as TasksState,
                    } as RootState,
                    modules: {
                        tasks: {
                            getters: {
                                has_at_least_one_row_shown: () => has_at_least_one_row_shown,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
            props: {
                value,
                available_natures,
            },
        });
    }

    it("should display a selectbox with available natures", () => {
        value = "depends_on";

        const wrapper = getWrapper();

        expect(isSelected(wrapper, "none")).toBe(false);
        expect(isSelected(wrapper, "")).toBe(false);
        expect(isSelected(wrapper, "depends_on")).toBe(true);
    });

    it("should emit input event when the value is changed", () => {
        const wrapper = getWrapper();

        wrapper.find(`[data-test=option-depends_on]`).setValue();
        wrapper.find(`[data-test=option-]`).setValue();
        wrapper.find(`[data-test=option-none]`).setValue();

        const input_event = wrapper.emitted("input");
        if (!input_event) {
            throw new Error("Failed to catch input event");
        }

        expect(input_event[0][0]).toBe("depends_on");
        expect(input_event[1][0]).toBe("");
        expect(input_event[2][0]).toBeNull();
    });

    it("should mark the selectbox as disabled when there is no link", () => {
        available_natures = new NaturesLabels([]);

        const wrapper = getWrapper();

        const select = wrapper.find("[data-test=select-links]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Unable to find the select");
        }
        expect(select.disabled).toBe(true);
    });

    it("should mark the selectbox as disabled when there is no rows", () => {
        has_at_least_one_row_shown = false;

        const wrapper = getWrapper();

        const select = wrapper.find("[data-test=select-links]").element;
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Unable to find the select");
        }
        expect(select.disabled).toBe(true);
    });
});
