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

import type { State } from "../../../../store/type";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-tests";
import FieldName from "./FieldName.vue";

describe("FieldName", () => {
    let state: State, mock_set_tracker_name: jest.Mock;

    beforeEach(() => {
        mock_set_tracker_name = jest.fn();
        state = {
            tracker_to_be_created: {
                name: "Kanban in the trees",
                shortname: "kanban_in_the_trees",
            },
        } as State;
    });

    function getWrapper(
        can_display_slugify_mode: boolean,
        is_name_already_used = false,
    ): VueWrapper {
        return shallowMount(FieldName, {
            global: {
                ...getGlobalTestOptions({
                    state,
                    getters: {
                        can_display_slugify_mode: () => can_display_slugify_mode,
                        is_name_already_used: () => is_name_already_used,
                    },
                    mutations: {
                        setTrackerName: mock_set_tracker_name,
                    },
                }),
            },
        });
    }

    it("its value is initialized with the tracker name from the store", () => {
        const wrapper = getWrapper(true);
        const input_element: HTMLInputElement = wrapper.get<HTMLInputElement>(
            "[data-test=tracker-name-input]",
        ).element;

        expect(input_element.value).toStrictEqual(state.tracker_to_be_created.name);
    });

    it("has the class tracker-name-above-slugified-shortname when slugify mode is active", () => {
        const wrapper = getWrapper(true);

        expect(wrapper.classes()).toContain("tracker-name-above-slugified-shortname");
    });

    it("sets the tracker name with the entered value on the keyup event", () => {
        const wrapper = getWrapper(true);
        const name_input = wrapper.get<HTMLInputElement>("[data-test=tracker-name-input]");

        name_input.trigger("keyup");

        expect(mock_set_tracker_name).toHaveBeenCalledWith(
            expect.anything(),
            name_input.element.value,
        );
    });

    it("Enters the error mode when the chosen name already exist", () => {
        const wrapper = getWrapper(true, true);

        expect(wrapper.classes()).toContain("tlp-form-element-error");
        expect(wrapper.find("[data-test=name-error]").exists()).toBe(true);
    });
});
